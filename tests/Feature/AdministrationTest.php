<?php

namespace Tests\Feature;

use App\Mail\TemplateMail;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BillingService;
use App\Support\RuntimeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\Concerns\BuildsBillingData;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use BuildsBillingData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseData();
    }

    public function test_maintenance_mode_blocks_everyone_but_administrators(): void
    {
        Setting::set(['maintenance_mode' => true, 'maintenance_message' => 'Back at 18:00']);
        $staff = User::factory()->create();

        $this->get('/login')->assertOk();
        $this->actingAs($staff)->get('/dashboard')->assertStatus(503)->assertSee('Back at 18:00');
        $this->actingAs($this->admin())->get('/dashboard')->assertOk()->assertSee('Maintenance mode');
    }

    public function test_web_cron_requires_the_secret_token(): void
    {
        $this->get('/cron/wrong-token')->assertForbidden();

        $this->get('/cron/'.Setting::get('cron_token'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['billing' => ['renewed', 'invoices', 'expired', 'overdue'], 'reminders' => ['renewal', 'overdue']]);
    }

    public function test_language_switcher_translates_the_interface(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/locale/es')->assertRedirect();
        $this->assertSame('es', $user->fresh()->locale);
        $this->actingAs($user->fresh())->get('/customers')->assertSee('Nuevo cliente');

        $this->actingAs($user->fresh())->get('/locale/pt');
        $this->actingAs($user->fresh())->get('/customers')->assertSee('Novo cliente');

        $this->get('/locale/xx')->assertNotFound();
    }

    public function test_theme_preferences_are_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/preferences', ['theme' => 'dark', 'accent' => 'emerald', 'surface' => 'slate'])->assertOk();
        $this->assertSame(['dark', 'emerald', 'slate'], [$user->fresh()->theme, $user->fresh()->accent, $user->fresh()->surface]);

        $this->actingAs($user)->postJson('/preferences', ['accent' => 'neon'])->assertUnprocessable();
    }

    public function test_settings_keep_secrets_when_left_blank(): void
    {
        $admin = $this->admin();
        Setting::set('mail_password', 'smtp-secret');

        $this->actingAs($admin)->put(route('admin.settings.update', 'mail'), [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_username' => 'robot',
            'mail_password' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'billing@example.com',
            'mail_from_name' => 'Billing',
        ])->assertSessionHas('success');

        $this->assertSame('smtp-secret', Setting::get('mail_password'));
        $this->assertSame('smtp.example.com', Setting::get('mail_host'));

        RuntimeConfig::applyMail();
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('smtp-secret', config('mail.mailers.smtp.password'));
    }

    public function test_only_admins_reach_the_settings(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.settings.edit', 'unknown'))->assertNotFound();
    }

    public function test_changing_the_base_currency_rebases_every_rate(): void
    {
        $eur = Currency::query()->where('code', 'EUR')->firstOrFail();

        $this->actingAs($this->admin())->post(route('currencies.default', $eur))->assertSessionHas('success');

        $this->assertSame('EUR', Currency::default()->code);
        $this->assertEqualsWithDelta(1 / 0.92, (float) Currency::query()->where('code', 'USD')->value('exchange_rate'), 0.0001);
    }

    public function test_invoices_can_be_created_from_the_generator(): void
    {
        Mail::fake();
        $customer = $this->makeCustomer();

        $this->actingAs($this->admin())->post(route('invoices.store'), [
            'customer_id' => $customer->id,
            'currency_id' => $this->usd()->id,
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-15',
            'status' => 'sent',
            'tax_rate' => 10,
            'send_email' => '1',
            'items' => [
                ['description' => 'Consulting', 'quantity' => 2, 'unit_price' => 100, 'discount' => 0, 'product_id' => '', 'plan_id' => ''],
                ['description' => 'Setup', 'quantity' => 1, 'unit_price' => 50, 'discount' => 50, 'product_id' => '', 'plan_id' => ''],
            ],
        ])->assertRedirect();

        $invoice = Invoice::query()->with('items')->sole();
        $this->assertCount(2, $invoice->items);
        $this->assertEquals(247.50, (float) $invoice->total);
        $this->assertNotNull($invoice->sent_at);
        Mail::assertSent(TemplateMail::class);
    }

    public function test_kanban_cards_can_be_moved(): void
    {
        $columns = KanbanColumn::query()->orderBy('sort_order')->get();
        $user = User::factory()->create();
        $card = KanbanCard::query()->create(['kanban_column_id' => $columns[0]->id, 'title' => 'Call customer', 'priority' => 'high']);
        $other = KanbanCard::query()->create(['kanban_column_id' => $columns[1]->id, 'title' => 'Other', 'priority' => 'low']);

        $this->actingAs($user)->postJson(route('kanban.move'), [
            'card' => $card->id,
            'column' => $columns[1]->id,
            'order' => [$card->id, $other->id],
        ])->assertOk();

        $this->assertSame($columns[1]->id, $card->fresh()->kanban_column_id);
        $this->assertSame(0, $card->fresh()->sort_order);
        $this->assertSame(1, $other->fresh()->sort_order);
    }

    public function test_seller_profile_with_login_is_scoped(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('sellers.store'), [
            'name' => 'Sam Seller',
            'email' => 'sam@example.com',
            'commission_rate' => 12.5,
            'is_active' => '1',
            'create_login' => '1',
            'password' => 'password123',
        ])->assertRedirect();

        $seller = Seller::query()->sole();
        $this->assertTrue($seller->user->isSeller());

        // Customers created by a seller are always assigned to them.
        $this->actingAs($seller->user)->post(route('customers.store'), ['name' => 'Own customer', 'status' => 'active', 'seller_id' => 999])->assertRedirect();
        $this->assertSame($seller->id, Customer::query()->where('name', 'Own customer')->value('seller_id'));
    }

    public function test_customers_can_cancel_from_the_portal(): void
    {
        $customer = $this->makeCustomer();
        $user = User::factory()->customer($customer->id)->create();
        $subscription = app(BillingService::class)->subscribe([
            'customer_id' => $customer->id,
            'plan_id' => $this->makePlan()->id,
            'start_date' => today()->toDateString(),
        ]);

        $this->actingAs($user)->post(route('portal.subscriptions.cancel', $subscription), ['reason' => 'Moving'])->assertSessionHas('success');

        $subscription->refresh();
        $this->assertFalse($subscription->auto_renew);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame(1, KanbanCard::query()->where('subscription_id', $subscription->id)->count());

        $intruder = User::factory()->customer($this->makeCustomer(['email' => 'x@example.com'])->id)->create();
        $this->actingAs($intruder)->post(route('portal.subscriptions.cancel', $subscription))->assertNotFound();
    }

    public function test_public_registration_creates_portal_customers_when_enabled(): void
    {
        $this->get('/register')->assertNotFound();

        Setting::set('allow_registration', true);

        $this->post('/register', [
            'name' => 'New Customer',
            'company' => 'Startup Inc',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('portal.dashboard'));

        $user = User::query()->where('email', 'new@example.com')->sole();
        $this->assertTrue($user->isCustomer());
        $this->assertSame('Startup Inc', $user->customer->company);
    }

    public function test_google_login_button_only_shows_when_configured(): void
    {
        $this->get('/login')->assertDontSee('Continue with Google');
        $this->get('/auth/google/redirect')->assertNotFound();

        Setting::set(['google_login_enabled' => true, 'google_client_id' => 'id.apps.googleusercontent.com', 'google_client_secret' => 'secret']);

        $this->get('/login')->assertSee('Continue with Google');
        $this->get('/auth/google/redirect')->assertRedirectContains('accounts.google.com');
    }

    public function test_google_callback_links_accounts_and_can_register_customers(): void
    {
        Setting::set(['google_login_enabled' => true, 'google_client_id' => 'id', 'google_client_secret' => 'secret']);
        $existing = User::factory()->create(['email' => 'grace@example.com']);

        $this->mockGoogleUser('google-1', 'grace@example.com');
        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-1', $existing->fresh()->google_id);
        Auth::logout();

        // Unknown users are rejected unless auto-registration is enabled.
        $this->mockGoogleUser('google-2', 'newbie@example.com');
        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();

        Setting::set('google_auto_register', true);
        $this->get('/auth/google/callback')->assertRedirect(route('portal.dashboard'));
        $this->assertTrue(User::query()->where('email', 'newbie@example.com')->sole()->isCustomer());
        Auth::logout();

        // Unverified Google e-mails are never trusted.
        $this->mockGoogleUser('google-3', 'grace@example.com', verified: false);
        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    protected function mockGoogleUser(string $id, string $email, bool $verified = true): void
    {
        $user = (new SocialiteUser)->setRaw(['email_verified' => $verified])
            ->map(['id' => $id, 'email' => $email, 'name' => 'Google User', 'avatar' => null]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($user);

        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('driver')->with('google')->andReturn($provider);

        Socialite::swap($factory);
    }

    public function test_inactive_users_are_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}

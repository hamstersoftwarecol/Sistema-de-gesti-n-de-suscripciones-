<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every screen of the application with realistic demo data.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_open_every_screen(): void
    {
        $admin = User::query()->where('email', 'admin@demo.com')->firstOrFail();
        $customer = Customer::query()->has('subscriptions')->firstOrFail();
        $subscription = Subscription::query()->firstOrFail();
        $invoice = Invoice::query()->has('payments')->firstOrFail();
        $payment = Payment::query()->firstOrFail();
        $product = Product::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $seller = Seller::query()->firstOrFail();
        $template = EmailTemplate::query()->firstOrFail();
        $field = CustomField::query()->firstOrFail();
        $conversation = AiConversation::query()->create(['user_id' => $admin->id, 'title' => 'Test']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Hola']);
        $conversation->messages()->create(['role' => 'model', 'content' => "**Hola**\n\n- uno\n- dos"]);

        $urls = [
            '/dashboard', '/search?q=Inno', '/calendar', '/kanban', '/kanban?mine=1',
            '/customers', '/customers?status=active&q=a', '/customers/create', "/customers/{$customer->id}", "/customers/{$customer->id}/edit",
            '/subscriptions', '/subscriptions?status=trial', '/subscriptions?renewal=month', '/subscriptions/create', "/subscriptions/create?customer_id={$customer->id}",
            "/subscriptions/{$subscription->id}", "/subscriptions/{$subscription->id}/edit",
            '/invoices', '/invoices?status=overdue', '/invoices/create', "/invoices/create?customer_id={$customer->id}",
            "/invoices/{$invoice->id}", "/invoices/{$invoice->id}/edit", "/invoices/{$invoice->id}/print",
            '/payments', '/payments/create', "/payments/create?invoice_id={$invoice->id}", "/payments/{$payment->id}", "/payments/{$payment->id}/edit",
            '/products', '/products/create', "/products/{$product->id}", "/products/{$product->id}/edit",
            '/categories', '/suppliers', '/suppliers/create', "/suppliers/{$supplier->id}", "/suppliers/{$supplier->id}/edit",
            '/sellers', '/sellers/create', "/sellers/{$seller->id}", "/sellers/{$seller->id}/edit",
            '/currencies', '/email-logs',
            '/ai', "/ai/conversations/{$conversation->id}", '/ai/insights',
            '/admin/settings', '/admin/settings/billing', '/admin/settings/reminders', '/admin/settings/mail',
            '/admin/settings/integrations', '/admin/settings/maintenance',
            '/admin/users', '/admin/users/create', "/admin/users/{$admin->id}/edit",
            '/admin/email-templates', "/admin/email-templates/{$template->id}/edit",
            '/admin/custom-fields', '/admin/custom-fields/create', "/admin/custom-fields/{$field->id}/edit",
            '/admin/backups', '/admin/activity', '/profile',
            '/manifest.webmanifest', '/offline',
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get("/invoices/{$invoice->id}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($admin)->getJson('/calendar/events?start='.now()->startOfMonth()->toDateString().'&end='.now()->endOfMonth()->addDays(10)->toDateString())
            ->assertOk()
            ->assertJsonStructure([['id', 'title', 'start', 'url']]);
    }

    public function test_seller_sees_only_their_own_records(): void
    {
        $sellerUser = User::query()->where('email', 'seller@demo.com')->firstOrFail();
        $own = Customer::query()->where('seller_id', $sellerUser->seller->id)->firstOrFail();
        $foreign = Customer::query()->where('seller_id', '!=', $sellerUser->seller->id)->firstOrFail();

        foreach (['/dashboard', '/customers', '/subscriptions', '/invoices', '/payments', '/calendar', '/kanban', '/my-commissions', "/customers/{$own->id}"] as $url) {
            $this->actingAs($sellerUser)->get($url)->assertOk();
        }

        $this->actingAs($sellerUser)->get("/customers/{$foreign->id}")->assertForbidden();
        $this->actingAs($sellerUser)->get('/admin/settings')->assertForbidden();
        $this->actingAs($sellerUser)->get('/products')->assertForbidden();
        $this->actingAs($sellerUser)->get('/invoices/create')->assertForbidden();

        $this->actingAs($sellerUser)->get('/customers')
            ->assertSee($own->name)
            ->assertDontSee($foreign->code);
    }

    public function test_customer_uses_the_portal(): void
    {
        $user = User::query()->where('email', 'cliente@demo.com')->firstOrFail();
        $subscription = $user->customer->subscriptions()->firstOrFail();
        $invoice = $user->customer->invoices()->where('status', '!=', 'draft')->firstOrFail();
        $foreignInvoice = Invoice::query()->where('customer_id', '!=', $user->customer_id)->firstOrFail();

        foreach (['/portal', '/portal/subscriptions', "/portal/subscriptions/{$subscription->id}", '/portal/invoices', "/portal/invoices/{$invoice->id}", '/portal/payments', '/profile'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }

        $this->actingAs($user)->get("/portal/invoices/{$invoice->id}/pdf")->assertOk();
        $this->actingAs($user)->get("/portal/invoices/{$foreignInvoice->id}")->assertNotFound();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('portal.dashboard'));
        $this->actingAs($user)->get('/customers')->assertRedirect(route('portal.dashboard'));
    }

    public function test_guest_pages_render(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertOk()->assertSee('Log in');
        $this->get('/forgot-password')->assertOk();
        $this->get('/register')->assertNotFound();
        $this->get('/install')->assertRedirect('/');
    }
}

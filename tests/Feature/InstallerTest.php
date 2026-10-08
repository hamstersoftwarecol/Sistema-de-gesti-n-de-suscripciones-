<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\KanbanColumn;
use App\Models\Setting;
use App\Models\User;
use App\Support\Installer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    protected string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep the flag file away from the real storage folder.
        $this->storage = sys_get_temp_dir().'/subserp-install-'.uniqid();
        File::ensureDirectoryExists($this->storage.'/app');
        File::ensureDirectoryExists($this->storage.'/framework/views');
        File::ensureDirectoryExists($this->storage.'/logs');
        $this->app->useStoragePath($this->storage);

        config(['app.installed' => null]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    public function test_everything_redirects_to_the_wizard_until_installed(): void
    {
        $this->assertFalse(Installer::isInstalled());

        $this->get('/login')->assertRedirect('/install');
        $this->get('/dashboard')->assertRedirect('/install');
        $this->get('/install')->assertOk()->assertSee('pdo_sqlite');
    }

    public function test_wizard_installs_the_application(): void
    {
        $this->get('/install/database')->assertOk();
        $this->post('/install/database', ['demo' => 0])->assertRedirect('/install/setup');
        $this->get('/install/setup')->assertOk();

        $this->post('/install/setup', [
            'company_name' => 'Acme Subscriptions',
            'company_email' => 'billing@acme.test',
            'locale' => 'es',
            'timezone' => 'America/Bogota',
            'currency' => 'COP',
            'name' => 'Grace Hopper',
            'email' => 'grace@acme.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'gemini_api_key' => 'my-gemini-key',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(Installer::isInstalled());
        $this->assertAuthenticated();

        $admin = User::query()->where('email', 'grace@acme.test')->firstOrFail();
        $this->assertTrue($admin->isAdmin());

        $this->assertSame('Acme Subscriptions', Setting::get('company_name'));
        $this->assertSame('es', Setting::get('default_locale'));
        $this->assertSame('my-gemini-key', Setting::get('gemini_api_key'));
        $this->assertSame('FAC-', Setting::get('invoice_prefix'));
        $this->assertNotEmpty(Setting::get('cron_token'));

        $base = Currency::default();
        $this->assertSame('COP', $base->code);
        $this->assertEquals(1, (float) $base->exchange_rate);
        $this->assertEqualsWithDelta(1 / 4000, (float) Currency::query()->where('code', 'USD')->value('exchange_rate'), 0.000001);

        $this->assertSame(6, EmailTemplate::query()->count());
        $this->assertSame('Por hacer', KanbanColumn::query()->orderBy('sort_order')->value('name'));

        // Once installed the wizard is closed.
        $this->get('/install')->assertRedirect('/');
    }

    public function test_setup_validates_the_administrator(): void
    {
        $this->post('/install/database');

        $this->post('/install/setup', [
            'company_name' => '',
            'locale' => 'xx',
            'timezone' => 'Mars/Base',
            'currency' => 'USD',
            'name' => 'A',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'other',
        ])->assertSessionHasErrors(['company_name', 'locale', 'timezone', 'email', 'password']);

        $this->assertFalse(Installer::isInstalled());
    }
}

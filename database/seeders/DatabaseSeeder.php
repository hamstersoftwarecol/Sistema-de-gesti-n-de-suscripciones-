<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Installer;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate --seed → ready-to-use demo installation.
     */
    public function run(): void
    {
        ActivityLog::$enabled = false;

        $this->callWith(BaseDataSeeder::class, ['locale' => config('app.locale', 'es')]);

        User::query()->firstOrCreate(['email' => 'admin@demo.com'], [
            'name' => 'Administrador',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->call(DemoDataSeeder::class);

        ActivityLog::$enabled = true;

        if (! app()->runningUnitTests()) {
            Installer::markInstalled();
        }
    }
}

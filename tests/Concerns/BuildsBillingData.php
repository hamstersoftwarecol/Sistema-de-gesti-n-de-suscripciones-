<?php

namespace Tests\Concerns;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BaseDataSeeder;

trait BuildsBillingData
{
    protected function seedBaseData(): void
    {
        (new BaseDataSeeder)->run('en', 'USD');
    }

    protected function usd(): Currency
    {
        return Currency::query()->where('code', 'USD')->first()
            ?? Currency::query()->create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_default' => true]);
    }

    protected function makePlan(array $attributes = []): Plan
    {
        $product = Product::query()->create(['name' => 'CRM Cloud', 'type' => 'software', 'is_active' => true]);

        return $product->plans()->create(array_merge([
            'name' => 'Pro monthly',
            'price' => 100,
            'currency_id' => $this->usd()->id,
            'billing_cycle' => 'monthly',
            'interval_count' => 1,
            'trial_days' => 0,
            'setup_fee' => 0,
            'is_active' => true,
        ], $attributes));
    }

    protected function makeCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'status' => 'active',
            'currency_id' => $this->usd()->id,
        ], $attributes));
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }
}

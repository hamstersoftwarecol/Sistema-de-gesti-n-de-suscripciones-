<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\KanbanColumn;
use App\Models\Setting;
use App\Support\DefaultContent;
use Illuminate\Database\Seeder;

/**
 * Data every installation needs: settings, currencies, e-mail templates and Kanban columns.
 * Safe to run more than once.
 */
class BaseDataSeeder extends Seeder
{
    public function run(string $locale = 'es', string $baseCurrency = 'USD'): void
    {
        foreach (DefaultContent::settings($locale) as $key => $value) {
            if (! Setting::query()->where('key', $key)->exists()) {
                Setting::set($key, $value);
            }
        }

        $this->seedCurrencies($baseCurrency);

        foreach (DefaultContent::emailTemplates($locale) as $key => $template) {
            EmailTemplate::query()->firstOrCreate(['key' => $key], $template + ['is_active' => true]);
        }

        if (KanbanColumn::query()->doesntExist()) {
            foreach (DefaultContent::kanbanColumns($locale) as $i => $column) {
                KanbanColumn::query()->create($column + ['sort_order' => $i]);
            }
        }
    }

    public function seedCurrencies(string $baseCode = 'USD'): void
    {
        $definitions = collect(DefaultContent::currencies());
        $base = $definitions->firstWhere('code', $baseCode) ?? $definitions->first();

        foreach ($definitions as $currency) {
            Currency::query()->firstOrCreate(['code' => $currency['code']], [
                'name' => $currency['name'],
                'symbol' => $currency['symbol'],
                'exchange_rate' => round($currency['usd_rate'] / $base['usd_rate'], 6),
                'decimal_places' => $currency['decimal_places'],
                'is_default' => $currency['code'] === $base['code'],
                'is_active' => true,
            ]);
        }
    }
}

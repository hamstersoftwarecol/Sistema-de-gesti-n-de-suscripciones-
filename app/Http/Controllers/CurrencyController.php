<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function index(): View
    {
        return view('currencies.index', [
            'currencies' => Currency::query()->orderByDesc('is_default')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Currency::query()->create($this->validated($request));

        return back()->with('success', __('Currency created.'));
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $data = $this->validated($request, $currency);

        if ($currency->is_default) {
            $data['exchange_rate'] = 1;
            $data['is_active'] = true;
        }

        $currency->update($data);

        return back()->with('success', __('Currency updated.'));
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return back()->with('error', __('The base currency cannot be deleted.'));
        }

        $currency->delete();

        return back()->with('success', __('Currency deleted.'));
    }

    /**
     * Make a currency the base: every other rate is re-expressed relative to it.
     */
    public function makeDefault(Currency $currency): RedirectResponse
    {
        $factor = (float) $currency->exchange_rate ?: 1;

        Currency::query()->whereKeyNot($currency->id)->get()->each(function (Currency $other) use ($factor) {
            $other->update(['exchange_rate' => round((float) $other->exchange_rate / $factor, 6)]);
        });

        $currency->makeDefault();

        return back()->with('success', __(':code is now the base currency.', ['code' => $currency->code]));
    }

    protected function validated(Request $request, ?Currency $currency = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:3', Rule::unique('currencies', 'code')->ignore($currency?->id)],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'gt:0'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:4'],
            'is_active' => ['boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);

        return $data;
    }
}

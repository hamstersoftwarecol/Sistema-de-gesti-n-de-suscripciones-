<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $product->plans()->create($this->validated($request));

        return back()->with('success', __('Plan created.'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validated($request));

        return back()->with('success', __('Plan updated.'));
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->withTrashed()->exists()) {
            $plan->update(['is_active' => false]);

            return back()->with('warning', __('The plan has subscriptions, so it was deactivated instead of deleted.'));
        }

        $plan->delete();

        return back()->with('success', __('Plan deleted.'));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'billing_cycle' => ['required', Rule::in(Plan::CYCLES)],
            'interval_count' => ['required', 'integer', 'min:1', 'max:36'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'features' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ]);

        return array_merge($data, [
            'trial_days' => $data['trial_days'] ?? 0,
            'setup_fee' => $data['setup_fee'] ?? 0,
        ]);
    }
}

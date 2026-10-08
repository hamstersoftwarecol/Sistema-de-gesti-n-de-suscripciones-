<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->withCount('products')
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('contact_name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::query()->create($this->validated($request));
        $supplier->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('suppliers.show', $supplier)->with('success', __('Supplier created.'));
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['products.plans.currency', 'products.category']);

        $monthlyCost = $supplier->products->sum(fn ($product) => (float) $product->cost
            * $product->plans->sum(fn ($plan) => $plan->subscriptions()->live()->sum('quantity')));

        return view('suppliers.show', compact('supplier', 'monthlyCost'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));
        $supplier->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('suppliers.show', $supplier)->with('success', __('Supplier updated.'));
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', __('Supplier deleted.'));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ] + Supplier::customFieldRules());

        return collect($data)->except('custom_fields')->all();
    }
}

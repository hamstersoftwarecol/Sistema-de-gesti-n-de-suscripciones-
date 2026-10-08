<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'supplier', 'plans.currency'])
            ->withCount(['plans as live_subscriptions_count' => fn ($q) => $q->join('subscriptions', 'subscriptions.plan_id', '=', 'plans.id')
                ->whereIn('subscriptions.status', ['trial', 'active', 'past_due'])->whereNull('subscriptions.deleted_at')])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('sku', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('product_category_id', $request->integer('category')))
            ->when($request->filled('supplier'), fn ($q) => $q->where('supplier_id', $request->integer('supplier')))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => ProductCategory::query()->orderBy('name')->pluck('name', 'id'),
            'suppliers' => Supplier::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('products.form', ['product' => new Product(['type' => 'service', 'is_active' => true, 'cost' => 0])] + $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::query()->create($this->validated($request));
        $product->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('products.show', $product)->with('success', __('Product created. Now add its pricing plans.'));
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'supplier', 'plans' => fn ($q) => $q->with('currency')->withCount(['subscriptions as live_count' => fn ($s) => $s->live()])]);

        return view('products.show', [
            'product' => $product,
            'currencies' => Currency::query()->active()->orderBy('code')->get()->mapWithKeys(fn ($c) => [$c->id => $c->label()]),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', ['product' => $product] + $this->formData());
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));
        $product->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('products.show', $product)->with('success', __('Product updated.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->plans()->whereHas('subscriptions')->exists()) {
            return back()->with('error', __('This product has subscriptions and cannot be deleted. Deactivate it instead.'));
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', __('Product deleted.'));
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($product?->id)],
            'type' => ['required', Rule::in(Product::TYPES)],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ] + Product::customFieldRules());

        return collect($data)->except('custom_fields')->all() + ['cost' => $data['cost'] ?? 0];
    }

    protected function formData(): array
    {
        return [
            'categories' => ProductCategory::query()->orderBy('name')->pluck('name', 'id'),
            'suppliers' => Supplier::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}

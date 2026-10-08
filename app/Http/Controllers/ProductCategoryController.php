<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => ProductCategory::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProductCategory::query()->create($this->validated($request));

        return back()->with('success', __('Category created.'));
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $category->update($this->validated($request));

        return back()->with('success', __('Category updated.'));
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', __('Category deleted.'));
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}

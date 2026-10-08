<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    public function index(): View
    {
        return view('admin.custom-fields.index', [
            'groups' => CustomField::query()->withCount('values')->orderBy('sort_order')->get()->groupBy('entity'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.custom-fields.form', ['field' => new CustomField([
            'entity' => $request->input('entity', 'customer'),
            'type' => 'text',
            'is_active' => true,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        CustomField::query()->create($this->validated($request));

        return redirect()->route('admin.custom-fields.index')->with('success', __('Custom field created.'));
    }

    public function edit(CustomField $customField): View
    {
        return view('admin.custom-fields.form', ['field' => $customField]);
    }

    public function update(Request $request, CustomField $customField): RedirectResponse
    {
        $customField->update($this->validated($request, $customField));

        return redirect()->route('admin.custom-fields.index')->with('success', __('Custom field updated.'));
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $customField->delete();

        return back()->with('success', __('Custom field deleted.'));
    }

    protected function validated(Request $request, ?CustomField $field = null): array
    {
        $request->merge(['name' => Str::snake(Str::ascii($request->input('name') ?: $request->input('label', '')))]);

        $data = $request->validate([
            'entity' => ['required', Rule::in(array_keys(CustomField::entityOptions()))],
            'label' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('custom_fields')->where('entity', $request->input('entity'))->ignore($field?->id)],
            'type' => ['required', Rule::in(CustomField::TYPES)],
            'options_text' => ['nullable', 'required_if:type,select', 'string', 'max:5000'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'show_in_table' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $data['options'] = $data['type'] === 'select'
            ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $data['options_text']))))
            : null;
        $data['sort_order'] ??= 0;
        unset($data['options_text']);

        return $data;
    }
}

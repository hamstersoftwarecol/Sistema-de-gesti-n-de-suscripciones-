<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('customer', 'seller')
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('email', 'like', '%'.$request->string('q').'%')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => User::ROLE_STAFF, 'is_active' => true])] + $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['email_verified_at'] = now();
        User::query()->create($data);

        return redirect()->route('admin.users.index')->with('success', __('User created.'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user] + $this->formData());
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Never lock yourself out.
        if ($user->is(auth()->user())) {
            $data['role'] = User::ROLE_ADMIN;
            $data['is_active'] = true;
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', __('User updated.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', __('You cannot delete your own account here.'));
        }

        $user->delete();

        return back()->with('success', __('User deleted.'));
    }

    protected function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(User::ROLES)],
            'customer_id' => ['nullable', 'required_if:role,customer', 'exists:customers,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', Rule::in(array_keys(config('app.available_locales')))],
            'is_active' => ['boolean'],
            'password' => [$user ? 'nullable' : 'required', Password::min(8)],
        ]);

        if ($data['role'] !== User::ROLE_CUSTOMER) {
            $data['customer_id'] = null;
        }

        return $data;
    }

    protected function formData(): array
    {
        return [
            'customers' => Customer::query()->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->displayName()]),
        ];
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        abort_unless(Setting::bool('allow_registration'), 404);

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(Setting::bool('allow_registration'), 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company' => ['nullable', 'string', 'max:255'],
        ]);

        // Self sign-ups become customers with access to the customer portal.
        $user = DB::transaction(function () use ($request) {
            $customer = Customer::create([
                'name' => $request->name,
                'company' => $request->company,
                'email' => $request->email,
                'status' => 'lead',
                'currency_id' => base_currency()?->id,
            ]);

            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_CUSTOMER,
                'customer_id' => $customer->id,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect($user->homeRoute());
    }
}

<?php

namespace App\Http\Controllers;

use App\Exceptions\GeminiException;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Seller;
use App\Models\User;
use App\Services\EmailService;
use App\Services\GeminiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $customers = Customer::query()
            ->visibleTo($user)
            ->with(['seller', 'currency', 'customFieldValues'])
            ->withCount(['subscriptions as live_subscriptions_count' => fn ($q) => $q->live()])
            ->when($request->filled('q'), function ($query) use ($request) {
                $like = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('company', 'like', $like)
                    ->orWhere('email', 'like', $like)->orWhere('code', 'like', $like)->orWhere('tax_id', 'like', $like));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('seller_id') && $user->isStaff(), fn ($q) => $q->where('seller_id', $request->integer('seller_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'sellers' => Seller::query()->orderBy('name')->pluck('name', 'id'),
            'tableFields' => CustomField::for('customer')->where('show_in_table', true),
        ]);
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => new Customer(['status' => 'active', 'currency_id' => base_currency()?->id])] + $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $customer = Customer::query()->create($data);
        $customer->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('customers.show', $customer)->with('success', __('Customer created successfully.'));
    }

    public function show(Customer $customer): View
    {
        $this->ensureVisible($customer);

        $customer->load([
            'seller', 'currency', 'user',
            'subscriptions' => fn ($q) => $q->with('plan.product', 'currency')->latest(),
            'invoices' => fn ($q) => $q->with('currency')->latest('issue_date')->take(12),
            'payments' => fn ($q) => $q->with('currency')->latest('paid_at')->take(12),
        ]);

        return view('customers.show', [
            'customer' => $customer,
            'balance' => $customer->outstandingBalance(),
            'mrr' => $customer->subscriptions->filter->isLive()->sum(fn ($s) => $s->monthlyAmountInBase()),
        ]);
    }

    public function edit(Customer $customer): View
    {
        $this->ensureVisible($customer);

        return view('customers.form', ['customer' => $customer] + $this->formData());
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureVisible($customer);

        $customer->update($this->validated($request, $customer));
        $customer->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('customers.show', $customer)->with('success', __('Customer updated successfully.'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', __('Customer deleted.'));
    }

    /** Create or update the customer's login for the self-service portal. */
    public function portalAccess(Request $request, Customer $customer, EmailService $emails): RedirectResponse
    {
        $existing = $customer->user;

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existing?->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'send_email' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $password = $data['password'] ?: Str::password(12, symbols: false);

        $user = $existing ?? new User(['role' => User::ROLE_CUSTOMER, 'customer_id' => $customer->id]);
        $user->fill([
            'name' => $customer->name,
            'email' => $data['email'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! $existing || filled($data['password'])) {
            $user->password = $password;
        }

        $user->email_verified_at ??= now();
        $user->save();

        if ($request->boolean('send_email') && (! $existing || filled($data['password']))) {
            $log = $emails->sendPortalWelcome($customer, $user->email, $password);

            if ($log?->status === 'failed') {
                return back()->with('warning', __('Portal access saved, but the e-mail could not be sent: :error', ['error' => $log->error]));
            }
        }

        $message = ! $existing || filled($data['password'])
            ? __('Portal access saved. Password: :password', ['password' => $password])
            : __('Portal access saved.');

        return back()->with('success', $message);
    }

    public function aiAnalysis(Customer $customer, GeminiService $gemini): RedirectResponse
    {
        try {
            $analysis = $gemini->analyzeCustomer($customer);
        } catch (GeminiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ai_analysis', $analysis);
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'seller_id' => ['nullable', 'exists:sellers,id'],
            'status' => ['required', Rule::in(Customer::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ] + Customer::customFieldRules());

        // Sellers always own the customers they register.
        if ($request->user()->isSeller()) {
            $data['seller_id'] = $request->user()->seller?->id;
        }

        return collect($data)->except('custom_fields')->all();
    }

    protected function formData(): array
    {
        return [
            'currencies' => Currency::query()->active()->orderBy('code')->get()->mapWithKeys(fn ($c) => [$c->id => $c->label()]),
            'sellers' => Seller::query()->active()->orderBy('name')->pluck('name', 'id'),
        ];
    }
}

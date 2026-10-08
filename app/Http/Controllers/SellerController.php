<?php

namespace App\Http\Controllers;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function index(): View
    {
        $from = now()->startOfMonth();

        $sellers = Seller::query()->with('user')->withCount(['customers', 'subscriptions as live_subscriptions_count' => fn ($q) => $q->live()])
            ->orderBy('name')->get()
            ->map(function (Seller $seller) use ($from) {
                $seller->revenue_month = $seller->revenueBetween($from, now());
                $seller->commission_month = round($seller->revenue_month * (float) $seller->commission_rate / 100, 2);

                return $seller;
            });

        return view('sellers.index', compact('sellers'));
    }

    public function create(): View
    {
        return view('sellers.form', ['seller' => new Seller(['is_active' => true, 'commission_rate' => 10])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $seller = Seller::query()->create($data['seller']);
        $this->syncUser($seller, $data);
        $seller->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('sellers.show', $seller)->with('success', __('Seller created.'));
    }

    public function show(Seller $seller): View
    {
        return view('sellers.show', $this->report($seller));
    }

    public function me(Request $request): View
    {
        $seller = $request->user()->seller;

        abort_unless($seller, 404, __('Your user is not linked to a seller profile.'));

        return view('sellers.show', $this->report($seller) + ['self' => true]);
    }

    public function edit(Seller $seller): View
    {
        return view('sellers.form', compact('seller'));
    }

    public function update(Request $request, Seller $seller): RedirectResponse
    {
        $data = $this->validated($request, $seller);
        $seller->update($data['seller']);
        $this->syncUser($seller, $data);
        $seller->saveCustomFields($request->input('custom_fields'));

        return redirect()->route('sellers.show', $seller)->with('success', __('Seller updated.'));
    }

    public function destroy(Seller $seller): RedirectResponse
    {
        $seller->delete();

        return redirect()->route('sellers.index')->with('success', __('Seller deleted.'));
    }

    protected function report(Seller $seller): array
    {
        $months = collect(range(5, 0))->map(function (int $ago) use ($seller) {
            $start = now()->startOfMonth()->subMonthsNoOverflow($ago);
            $end = $start->copy()->endOfMonth();
            $revenue = $seller->revenueBetween($start, $end);

            return [
                'label' => ucfirst($start->translatedFormat('M Y')),
                'revenue' => round($revenue, 2),
                'commission' => round($revenue * (float) $seller->commission_rate / 100, 2),
            ];
        });

        $current = $months->last();

        return [
            'seller' => $seller->load(['user', 'customers' => fn ($q) => $q->withCount(['subscriptions as live_count' => fn ($s) => $s->live()])->latest()->take(15)]),
            'months' => $months,
            'current' => $current,
            'liveSubscriptions' => $seller->subscriptions()->live()->with('plan', 'currency')->get(),
            'recentPayments' => $seller->attributedPayments()->with('customer', 'invoice')->latest('paid_at')->take(10)->get(),
            'targetProgress' => (float) $seller->monthly_target > 0 ? min(100, round($current['revenue'] / (float) $seller->monthly_target * 100)) : null,
        ];
    }

    protected function validated(Request $request, ?Seller $seller = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'monthly_target' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'create_login' => ['boolean'],
            'password' => [
                Rule::requiredIf(fn () => $request->boolean('create_login') && ! $seller?->user_id),
                'nullable', 'string', 'min:8',
            ],
        ] + Seller::customFieldRules());

        if ($request->boolean('create_login')) {
            $request->validate([
                'email' => ['required', Rule::unique('users', 'email')->ignore($seller?->user_id)],
            ]);
        }

        return [
            'seller' => collect($data)->only(['name', 'email', 'phone', 'commission_rate', 'monthly_target', 'is_active', 'notes'])->all(),
            'create_login' => $request->boolean('create_login'),
            'password' => $data['password'] ?? null,
        ];
    }

    /** Create or update the seller's login (role "seller"). */
    protected function syncUser(Seller $seller, array $data): void
    {
        if (! $data['create_login']) {
            return;
        }

        $user = $seller->user ?? new User(['role' => User::ROLE_SELLER, 'email_verified_at' => now()]);
        $user->fill(['name' => $seller->name, 'email' => $seller->email, 'is_active' => $seller->is_active]);

        if ($data['password']) {
            $user->password = $data['password'];
        }

        $user->save();

        if (! $seller->user_id) {
            $seller->update(['user_id' => $user->id]);
        }
    }
}

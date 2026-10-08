<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = trim((string) $request->query('q'));
        $user = $request->user();
        $results = ['customers' => collect(), 'subscriptions' => collect(), 'invoices' => collect()];

        if (mb_strlen($term) >= 2) {
            $like = "%{$term}%";

            $results['customers'] = Customer::query()->visibleTo($user)
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('company', 'like', $like)
                    ->orWhere('email', 'like', $like)->orWhere('code', 'like', $like)->orWhere('tax_id', 'like', $like))
                ->take(10)->get();

            $results['subscriptions'] = Subscription::query()->visibleTo($user)->with('customer', 'plan.product')
                ->where(fn ($q) => $q->where('reference', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('company', 'like', $like))
                    ->orWhereHas('plan', fn ($p) => $p->where('name', 'like', $like)))
                ->take(10)->get();

            $results['invoices'] = Invoice::query()->visibleTo($user)->with('customer', 'currency')
                ->where(fn ($q) => $q->where('number', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('company', 'like', $like)))
                ->latest('issue_date')->take(10)->get();
        }

        return view('search', compact('term', 'results'));
    }
}

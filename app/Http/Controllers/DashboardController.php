<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\KanbanCard;
use App\Models\Payment;
use App\Services\BusinessMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BusinessMetrics $metrics): View
    {
        $user = $request->user();

        return view('dashboard', [
            'summary' => $metrics->summary($user),
            'revenue' => $metrics->revenueByMonth(12, $user),
            'byStatus' => $metrics->subscriptionsByStatus($user),
            'byPlan' => $metrics->mrrByPlan(6, $user),
            'renewals' => $metrics->upcomingRenewals(14, 8, $user),
            'overdueInvoices' => Invoice::query()->visibleTo($user)->with('customer', 'currency')
                ->where('status', Invoice::STATUS_OVERDUE)->orderBy('due_date')->take(6)->get(),
            'recentPayments' => Payment::query()->visibleTo($user)->with('customer', 'currency')
                ->completed()->latest('paid_at')->latest('id')->take(6)->get(),
            'myTasks' => KanbanCard::query()->with('column')
                ->where('assigned_to', $user->id)
                ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
                ->orderByRaw('due_date is null, due_date')
                ->take(5)->get(),
        ]);
    }
}

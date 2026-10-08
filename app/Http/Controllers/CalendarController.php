<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\KanbanCard;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        return view('calendar.index');
    }

    /**
     * FullCalendar event feed: renewals, invoice due dates, trial endings and Kanban tasks.
     */
    public function events(Request $request): JsonResponse
    {
        $request->validate(['start' => ['required', 'date'], 'end' => ['required', 'date']]);

        $start = Carbon::parse($request->input('start'))->toDateString();
        $end = Carbon::parse($request->input('end'))->toDateString();
        $types = explode(',', (string) $request->input('types', 'renewal,invoice,trial,task'));
        $user = $request->user();
        $events = collect();

        if (in_array('renewal', $types, true)) {
            Subscription::query()->visibleTo($user)->with('customer', 'plan.product', 'currency')->live()
                ->whereBetween('next_billing_date', [$start, $end])->get()
                ->each(fn (Subscription $s) => $events->push([
                    'id' => 'sub-'.$s->id,
                    'title' => '↻ '.$s->customer?->name.' · '.$s->currency?->format($s->periodAmount()),
                    'start' => $s->next_billing_date->toDateString(),
                    'allDay' => true,
                    'url' => route('subscriptions.show', $s),
                    'color' => $s->auto_renew ? '#6366f1' : '#94a3b8',
                    'extendedProps' => ['type' => 'renewal'],
                ]));
        }

        if (in_array('trial', $types, true)) {
            Subscription::query()->visibleTo($user)->with('customer')
                ->where('status', Subscription::STATUS_TRIAL)
                ->whereBetween('trial_ends_at', [$start, $end])->get()
                ->each(fn (Subscription $s) => $events->push([
                    'id' => 'trial-'.$s->id,
                    'title' => '⏳ '.__('Trial ends').': '.$s->customer?->name,
                    'start' => $s->trial_ends_at->toDateString(),
                    'allDay' => true,
                    'url' => route('subscriptions.show', $s),
                    'color' => '#0ea5e9',
                    'extendedProps' => ['type' => 'trial'],
                ]));
        }

        if (in_array('invoice', $types, true)) {
            Invoice::query()->visibleTo($user)->with('customer', 'currency')
                ->whereIn('status', Invoice::OPEN_STATUSES)
                ->whereBetween('due_date', [$start, $end])->get()
                ->each(fn (Invoice $i) => $events->push([
                    'id' => 'inv-'.$i->id,
                    'title' => '🧾 '.$i->number.' · '.$i->format($i->balance()),
                    'start' => $i->due_date->toDateString(),
                    'allDay' => true,
                    'url' => route('invoices.show', $i),
                    'color' => $i->status === Invoice::STATUS_OVERDUE ? '#ef4444' : '#f59e0b',
                    'extendedProps' => ['type' => 'invoice'],
                ]));
        }

        if (in_array('task', $types, true)) {
            KanbanCard::query()->with('column')
                ->when($user->isSeller(), fn ($q) => $q->where('assigned_to', $user->id))
                ->whereBetween('due_date', [$start, $end])->get()
                ->each(fn (KanbanCard $card) => $events->push([
                    'id' => 'task-'.$card->id,
                    'title' => '✓ '.$card->title,
                    'start' => $card->due_date->toDateString(),
                    'allDay' => true,
                    'url' => route('kanban.index'),
                    'color' => $card->column?->is_done_column ? '#10b981' : '#8b5cf6',
                    'extendedProps' => ['type' => 'task'],
                ]));
        }

        return response()->json($events->values());
    }
}

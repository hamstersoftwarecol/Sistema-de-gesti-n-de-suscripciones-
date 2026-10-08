<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KanbanController extends Controller
{
    public function index(Request $request): View
    {
        $columns = KanbanColumn::query()
            ->with(['cards' => fn ($q) => $q->with(['assignee', 'customer', 'subscription'])
                ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $request->user()->id))
                ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))])
            ->orderBy('sort_order')
            ->get();

        return view('kanban.index', [
            'columns' => $columns,
            'users' => User::query()->where('role', '!=', User::ROLE_CUSTOMER)->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'customers' => Customer::query()->visibleTo($request->user())->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    /** Persist drag & drop: move a card to a column and store the new order. */
    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'card' => ['required', 'exists:kanban_cards,id'],
            'column' => ['required', 'exists:kanban_columns,id'],
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data) {
            KanbanCard::query()->whereKey($data['card'])->update(['kanban_column_id' => $data['column']]);

            foreach (array_values($data['order']) as $position => $id) {
                KanbanCard::query()->whereKey($id)->where('kanban_column_id', $data['column'])->update(['sort_order' => $position]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function storeCard(Request $request): RedirectResponse
    {
        $data = $this->validatedCard($request);
        $data['created_by'] = $request->user()->id;
        $data['sort_order'] = (int) KanbanCard::query()->where('kanban_column_id', $data['kanban_column_id'])->max('sort_order') + 1;

        KanbanCard::query()->create($data);

        return back()->with('success', __('Task created.'));
    }

    public function updateCard(Request $request, KanbanCard $card): RedirectResponse
    {
        $card->update($this->validatedCard($request));

        return back()->with('success', __('Task updated.'));
    }

    public function destroyCard(KanbanCard $card): RedirectResponse
    {
        $card->delete();

        return back()->with('success', __('Task deleted.'));
    }

    public function storeColumn(Request $request): RedirectResponse
    {
        $data = $this->validatedColumn($request);
        $data['sort_order'] = (int) KanbanColumn::query()->max('sort_order') + 1;
        KanbanColumn::query()->create($data);

        return back()->with('success', __('Column created.'));
    }

    public function updateColumn(Request $request, KanbanColumn $column): RedirectResponse
    {
        $column->update($this->validatedColumn($request));

        return back()->with('success', __('Column updated.'));
    }

    public function destroyColumn(KanbanColumn $column): RedirectResponse
    {
        if ($column->cards()->exists()) {
            return back()->with('error', __('Move or delete the tasks of this column first.'));
        }

        $column->delete();

        return back()->with('success', __('Column deleted.'));
    }

    protected function validatedCard(Request $request): array
    {
        return $request->validate([
            'kanban_column_id' => ['required', 'exists:kanban_columns,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(KanbanCard::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
        ]);
    }

    protected function validatedColumn(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_done_column' => ['boolean'],
        ]);

        return $data + ['is_done_column' => false];
    }
}

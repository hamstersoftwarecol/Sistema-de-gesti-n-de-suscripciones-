<x-app-layout :title="__('Kanban board')">
    @php $canManage = auth()->user()->isStaff(); @endphp
    <x-page-header :title="__('Kanban board')" :subtitle="__('Drag the cards between columns to update their status.')">
        <x-slot:actions>
            <a href="{{ request()->boolean('mine') ? route('kanban.index') : route('kanban.index', ['mine' => 1]) }}" @class(['btn-secondary', 'ring-2 ring-primary-500' => request()->boolean('mine')])>
                <x-icon name="user" class="h-4 w-4" /> {{ __('My tasks') }}
            </a>
            @if ($canManage)
                <button type="button" class="btn-secondary" x-data @click="$dispatch('open-modal', 'column-new')"><x-icon name="kanban" class="h-4 w-4" /> {{ __('New column') }}</button>
            @endif
            <button type="button" class="btn-primary" x-data @click="$dispatch('open-modal', 'card-new')"><x-icon name="plus" class="h-4 w-4" /> {{ __('New task') }}</button>
        </x-slot:actions>
    </x-page-header>

    <div x-data="kanban(@js(route('kanban.move')))" class="relative">
        <div x-show="saving" x-cloak class="absolute -top-8 right-0 text-xs text-gray-500">{{ __('Saving...') }}</div>
        <div class="-mx-3 flex snap-x gap-4 overflow-x-auto px-3 pb-4 sm:mx-0 sm:px-0">
            @foreach ($columns as $column)
                <section class="flex w-[85vw] max-w-xs shrink-0 snap-start flex-col rounded-xl bg-gray-200/60 dark:bg-gray-900/70 sm:w-80 xl:w-auto xl:min-w-[15rem] xl:max-w-none xl:flex-1">
                    <header class="flex items-center justify-between gap-2 px-3 py-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $column->color }}"></span>
                            <h2 class="truncate text-sm font-semibold">{{ $column->name }}</h2>
                            <span class="rounded-full bg-white px-2 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300" data-count-for="{{ $column->id }}">{{ $column->cards->count() }}</span>
                        </div>
                        @if ($canManage)
                            <button type="button" class="btn-icon h-7 w-7" @click="$dispatch('open-modal', 'column-{{ $column->id }}')"><x-icon name="dots" class="h-4 w-4" /></button>
                        @endif
                    </header>

                    <div class="flex min-h-[8rem] flex-1 flex-col gap-2 px-2 pb-3" data-kanban-list data-column-id="{{ $column->id }}">
                        @foreach ($column->cards as $card)
                            <article data-card-id="{{ $card->id }}" class="cursor-grab rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-200 active:cursor-grabbing dark:bg-gray-800 dark:ring-gray-700"
                                     @dblclick="$dispatch('open-modal', 'card-{{ $card->id }}')">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium {{ $column->is_done_column ? 'text-gray-500 line-through' : '' }}">{{ $card->title }}</p>
                                    <button type="button" class="btn-icon -mr-1 -mt-1 h-6 w-6 shrink-0" @click="$dispatch('open-modal', 'card-{{ $card->id }}')"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                                </div>
                                @if ($card->description)
                                    <p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ $card->description }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                                    <x-status :value="$card->priority" type="priority" />
                                    @if ($card->due_date)
                                        <span @class(['inline-flex items-center gap-1', 'text-red-600 dark:text-red-400 font-medium' => $card->isOverdue(), 'text-gray-500' => ! $card->isOverdue()])>
                                            <x-icon name="clock" class="h-3.5 w-3.5" /> {{ fdate($card->due_date) }}
                                        </span>
                                    @endif
                                    @if ($card->customer)
                                        <a href="{{ route('customers.show', $card->customer_id) }}" class="truncate text-primary-600 hover:underline dark:text-primary-400">{{ $card->customer->name }}</a>
                                    @endif
                                    @if ($card->assignee)
                                        <span class="ml-auto flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-[10px] font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-300" title="{{ $card->assignee->name }}">{{ $card->assignee->initials() }}</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    {{-- Card modals --}}
    @foreach (array_merge([null], $columns->flatMap->cards->all()) as $card)
        <x-modal :name="$card ? 'card-'.$card->id : 'card-new'" maxWidth="lg">
            <form method="POST" action="{{ $card ? route('kanban.cards.update', $card) : route('kanban.cards.store') }}" class="space-y-4 p-6">
                @csrf
                @if ($card) @method('PUT') @endif
                <h2 class="text-lg font-semibold">{{ $card ? __('Edit task') : __('New task') }}</h2>
                @php $sfx = $card?->id ?? 'new'; @endphp
                <x-forms.input name="title" :label="__('Title')" :value="$card?->title" required :id="'k_title_'.$sfx" />
                <x-forms.textarea name="description" :label="__('Description')" :value="$card?->description" :id="'k_desc_'.$sfx" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-forms.select name="kanban_column_id" :label="__('Column')" :options="$columns->pluck('name', 'id')" :value="$card?->kanban_column_id ?? $columns->first()?->id" required :id="'k_col_'.$sfx" />
                    <x-forms.select name="priority" :label="__('Priority')" :options="\App\Models\KanbanCard::priorityOptions()" :value="$card?->priority ?? 'medium'" required :id="'k_pri_'.$sfx" />
                    <x-forms.input name="due_date" type="date" :label="__('Due date')" :value="$card?->due_date" :id="'k_due_'.$sfx" />
                    <x-forms.select name="assigned_to" :label="__('Assigned to')" :options="$users" :value="$card?->assigned_to ?? auth()->id()" :placeholder="__('Nobody')" :id="'k_user_'.$sfx" />
                    <x-forms.select name="customer_id" :label="__('Related customer')" :options="$customers" :value="$card?->customer_id" :placeholder="__('None')" class="sm:col-span-2" :id="'k_cust_'.$sfx" />
                </div>
                <div class="flex items-center justify-between gap-2 pt-2">
                    <div>
                        @if ($card)
                            <button type="submit" form="delete-card-{{ $card->id }}" class="btn-ghost text-red-600">{{ __('Delete') }}</button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('Cancel') }}</button>
                        <button class="btn-primary">{{ __('Save') }}</button>
                    </div>
                </div>
            </form>
            @if ($card)
                <form id="delete-card-{{ $card->id }}" method="POST" action="{{ route('kanban.cards.destroy', $card) }}" onsubmit="return confirm(@js(__('Delete this task?')))">@csrf @method('DELETE')</form>
            @endif
        </x-modal>
    @endforeach

    {{-- Column modals --}}
    @if ($canManage)
        @foreach (array_merge([null], $columns->all()) as $column)
            <x-modal :name="$column ? 'column-'.$column->id : 'column-new'" maxWidth="md">
                <form method="POST" action="{{ $column ? route('kanban.columns.update', $column) : route('kanban.columns.store') }}" class="space-y-4 p-6">
                    @csrf
                    @if ($column) @method('PUT') @endif
                    <h2 class="text-lg font-semibold">{{ $column ? __('Edit column') : __('New column') }}</h2>
                    <x-forms.input name="name" :label="__('Name')" :value="$column?->name" required :id="'col_name_'.($column?->id ?? 'new')" />
                    <x-forms.input name="color" type="color" :label="__('Color')" :value="$column?->color ?? '#6366f1'" class="w-24" :id="'col_color_'.($column?->id ?? 'new')" />
                    <x-forms.checkbox name="is_done_column" :label="__('Tasks in this column are completed')" :checked="$column?->is_done_column" :id="'col_done_'.($column?->id ?? 'new')" />
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            @if ($column)
                                <button type="submit" form="delete-column-{{ $column->id }}" class="btn-ghost text-red-600">{{ __('Delete') }}</button>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">{{ __('Cancel') }}</button>
                            <button class="btn-primary">{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
                @if ($column)
                    <form id="delete-column-{{ $column->id }}" method="POST" action="{{ route('kanban.columns.destroy', $column) }}" onsubmit="return confirm(@js(__('Delete this column?')))">@csrf @method('DELETE')</form>
                @endif
            </x-modal>
        @endforeach
    @endif
</x-app-layout>

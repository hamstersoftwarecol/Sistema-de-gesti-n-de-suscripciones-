<x-app-layout :title="__('Calendar')">
    <x-page-header :title="__('Calendar')" :subtitle="__('Renewals, invoice due dates, trial endings and tasks')" />

    @php
        $legend = [
            'renewal' => ['#6366f1', __('Renewals')],
            'invoice' => ['#f59e0b', __('Invoices due')],
            'trial' => ['#0ea5e9', __('Trials ending')],
            'task' => ['#8b5cf6', __('Tasks')],
        ];
    @endphp

    <div class="card" x-data="calendar(@js(route('calendar.events')), @js(app()->getLocale()))">
        <div class="flex flex-wrap gap-2 border-b border-gray-200 p-4 dark:border-gray-800">
            @foreach ($legend as $type => [$color, $label])
                <button type="button" @click="toggle('{{ $type }}')"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-gray-200 transition dark:ring-gray-700"
                        :class="filters.includes('{{ $type }}') ? 'bg-white dark:bg-gray-800' : 'opacity-40'">
                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $color }}"></span> {{ $label }}
                </button>
            @endforeach
            <span class="ml-auto hidden text-xs text-gray-500 sm:inline">{{ __('Red: overdue invoice · Grey: renewal without auto-renew') }}</span>
        </div>
        <div class="p-2 sm:p-4">
            <div x-ref="calendar" class="min-h-[480px]"></div>
        </div>
    </div>
</x-app-layout>

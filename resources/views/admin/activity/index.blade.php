<x-app-layout :title="__('Activity log')">
    <x-page-header :title="__('Activity log')" :subtitle="__('Audit trail of every change in the system')" />

    <div class="card">
        <form method="GET" class="flex flex-wrap gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
            <input type="search" name="q" value="{{ request('q') }}" class="input min-w-[12rem] flex-1" placeholder="{{ __('Search description') }}">
            <select name="user_id" class="input w-auto">
                <option value="">{{ __('All users') }}</option>
                @foreach ($users as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('user_id') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="action" class="input w-auto">
                <option value="">{{ __('All actions') }}</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ __(ucfirst($action)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> {{ __('Filter') }}</button>
        </form>
        @if ($logs->isEmpty())
            <x-empty icon="clipboard" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('User') }}</th><th>{{ __('Action') }}</th><th>{{ __('Description') }}</th><th>IP</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $colors = ['created' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', 'updated' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', 'deleted' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'];
                            @endphp
                            <tr>
                                <td class="text-xs">{{ fdate($log->created_at, true) }}</td>
                                <td>{{ $log->user?->name ?? __('System') }}</td>
                                <td><span class="badge {{ $colors[$log->action] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">{{ __(ucfirst($log->action)) }}</span></td>
                                <td class="whitespace-normal">
                                    {{ $log->description }}
                                    @if (! empty($log->properties['changes']))
                                        <p class="text-xs text-gray-500">{{ implode(', ', $log->properties['changes']) }}</p>
                                    @endif
                                </td>
                                <td class="text-xs text-gray-500">{{ $log->ip_address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $logs->links() }}</div>
        @endif
    </div>
</x-app-layout>

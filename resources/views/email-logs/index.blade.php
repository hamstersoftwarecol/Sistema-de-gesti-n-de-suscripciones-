<x-app-layout :title="__('Email log')">
    <x-page-header :title="__('Email log')" :subtitle="__('Every reminder, invoice and notification sent by the system')" />

    <div class="card">
        <form method="GET" class="flex flex-wrap gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
            <input type="search" name="q" value="{{ request('q') }}" class="input min-w-[12rem] flex-1" placeholder="{{ __('Recipient or subject') }}">
            <select name="template" class="input w-auto">
                <option value="">{{ __('All templates') }}</option>
                @foreach ($templates as $template)
                    <option value="{{ $template }}" @selected(request('template') === $template)>{{ $template }}</option>
                @endforeach
            </select>
            <select name="status" class="input w-auto">
                <option value="">{{ __('All statuses') }}</option>
                <option value="sent" @selected(request('status') === 'sent')>{{ __('Sent') }}</option>
                <option value="failed" @selected(request('status') === 'failed')>{{ __('Failed') }}</option>
            </select>
            <button class="btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> {{ __('Filter') }}</button>
        </form>
        @if ($logs->isEmpty())
            <x-empty :message="__('No e-mails sent yet.')" icon="envelope" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Recipient') }}</th><th>{{ __('Subject') }}</th><th>{{ __('Template') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="text-xs">{{ fdate($log->created_at, true) }}</td>
                                <td>{{ $log->to }}<p class="text-xs text-gray-500">{{ $log->customer?->name }}</p></td>
                                <td class="max-w-xs truncate"><a href="{{ route('email-logs.show', $log) }}" class="link">{{ $log->subject }}</a></td>
                                <td class="font-mono text-xs">{{ $log->template_key }}</td>
                                <td><x-status :value="$log->status" type="email" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $logs->links() }}</div>
        @endif
    </div>
</x-app-layout>

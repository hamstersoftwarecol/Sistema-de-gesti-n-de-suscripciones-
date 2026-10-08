<x-install-layout>
    <div class="card">
        <div class="card-body space-y-4">
            <h1 class="text-2xl font-bold">{{ __('Welcome!') }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('This wizard will prepare the SQLite database, create your company and the administrator account in less than a minute.') }}</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="label">{{ __('Server requirements') }}</p>
                    <ul class="space-y-1 text-sm">
                        @foreach ($requirements as $check)
                            <li class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-1.5 dark:bg-gray-800/60">
                                <span>{{ $check['label'] }} @isset($check['value'])<span class="text-xs text-gray-500">({{ $check['value'] }})</span>@endisset</span>
                                <x-icon :name="$check['ok'] ? 'check-circle' : 'x-circle'" @class(['h-5 w-5', 'text-emerald-500' => $check['ok'], 'text-red-500' => ! $check['ok']]) />
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="label">{{ __('Writable folders') }}</p>
                    <ul class="space-y-1 text-sm">
                        @foreach ($permissions as $check)
                            <li class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-1.5 dark:bg-gray-800/60">
                                <span class="font-mono text-xs">{{ $check['label'] }}</span>
                                <x-icon :name="$check['ok'] ? 'check-circle' : 'x-circle'" @class(['h-5 w-5', 'text-emerald-500' => $check['ok'], 'text-red-500' => ! $check['ok']]) />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="flex justify-end border-t border-gray-200 p-4 dark:border-gray-800">
            @if ($passes)
                <a href="{{ route('install.database') }}" class="btn-primary">{{ __('Continue') }} <x-icon name="chevron-right" class="h-4 w-4" /></a>
            @else
                <p class="text-sm text-red-600">{{ __('Fix the items marked in red and reload this page.') }}</p>
            @endif
        </div>
    </div>
</x-install-layout>

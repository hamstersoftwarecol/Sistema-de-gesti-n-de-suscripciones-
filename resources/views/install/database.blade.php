<x-install-layout>
    <form method="POST" action="{{ route('install.migrate') }}" class="card" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <div class="card-body space-y-4">
            <h1 class="text-2xl font-bold">{{ __('Database') }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('The application uses SQLite: no database server is needed. The file will be created automatically at:') }}</p>
            <code class="block break-all rounded-lg bg-gray-100 px-3 py-2 text-xs dark:bg-gray-800">{{ $path }}</code>
            @if ($migrated)
                <p class="flex items-center gap-2 text-sm text-emerald-600"><x-icon name="check-circle" class="h-5 w-5" /> {{ __('The tables already exist. Pending migrations will be applied.') }}</p>
            @endif
            <label class="flex items-start gap-3 rounded-lg p-3 ring-1 ring-gray-200 dark:ring-gray-700">
                <input type="checkbox" name="demo" value="1" class="checkbox mt-0.5">
                <span>
                    <span class="text-sm font-medium">{{ __('Load demo data') }}</span>
                    <span class="block text-xs text-gray-500">{{ __('Sample customers, products, subscriptions, invoices and payments to explore the system. Demo users: seller@demo.com and cliente@demo.com (password: password).') }}</span>
                </span>
            </label>
        </div>
        <div class="flex justify-between border-t border-gray-200 p-4 dark:border-gray-800">
            <a href="{{ route('install.welcome') }}" class="btn-secondary">{{ __('Back') }}</a>
            <button class="btn-primary" :disabled="loading">
                <span x-show="!loading">{{ __('Create database') }}</span><span x-show="loading" x-cloak>{{ __('Working...') }}</span>
                <x-icon name="chevron-right" class="h-4 w-4" />
            </button>
        </div>
    </form>
</x-install-layout>

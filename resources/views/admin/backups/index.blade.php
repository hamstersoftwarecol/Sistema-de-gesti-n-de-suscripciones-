<x-app-layout :title="__('Backups')">
    <x-page-header :title="__('Database backups')" :subtitle="__('Create, download and restore copies of the SQLite database')">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.backups.store') }}">@csrf
                <button class="btn-primary"><x-icon name="database" class="h-4 w-4" /> {{ __('Create backup now') }}</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if ($error)
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-200">{{ $error }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <x-stat :label="__('Current database size')" :value="\Illuminate\Support\Number::fileSize($databaseSize)" icon="database" />
            <x-stat :label="__('Stored backups')" :value="$backups->count()" icon="duplicate" color="blue"
                    :hint="setting('auto_backup') ? __('Automatic daily backup enabled') : __('Automatic backup disabled')" />

            <form method="POST" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data" class="card card-body space-y-3"
                  onsubmit="return confirm(@js(__('The current database will be REPLACED by the uploaded file. A safety copy will be created first. Continue?')))">
                @csrf
                <p class="font-semibold">{{ __('Restore from a file') }}</p>
                <p class="text-sm text-gray-500">{{ __('Upload a .sqlite backup previously downloaded from this system.') }}</p>
                <input type="file" name="file" accept=".sqlite,.db,.sqlite3" required class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 dark:text-gray-300 dark:file:bg-primary-500/10 dark:file:text-primary-300">
                @error('file') <p class="input-error">{{ $message }}</p> @enderror
                <button class="btn-danger w-full"><x-icon name="upload" class="h-4 w-4" /> {{ __('Upload & restore') }}</button>
            </form>
        </div>

        <div class="card lg:col-span-2">
            @if ($backups->isEmpty())
                <x-empty :message="__('No backups yet.')" icon="database" />
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>{{ __('File') }}</th><th>{{ __('Date') }}</th><th>{{ __('Size') }}</th><th class="text-right">{{ __('Actions') }}</th></tr></thead>
                        <tbody>
                            @foreach ($backups as $backup)
                                <tr>
                                    <td class="font-mono text-xs">{{ $backup['name'] }}</td>
                                    <td>{{ fdate($backup['date'], true) }}</td>
                                    <td>{{ \Illuminate\Support\Number::fileSize($backup['size']) }}</td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <a href="{{ route('admin.backups.download', $backup['name']) }}" class="btn-icon h-8 w-8" title="{{ __('Download') }}"><x-icon name="download" class="h-4 w-4" /></a>
                                            <form method="POST" action="{{ route('admin.backups.restore', $backup['name']) }}"
                                                  onsubmit="return confirm(@js(__('Restore this backup? The current data will be replaced (a safety copy is created first).')))">@csrf
                                                <button class="btn-icon h-8 w-8 hover:text-amber-600" title="{{ __('Restore') }}"><x-icon name="refresh" class="h-4 w-4" /></button>
                                            </form>
                                            <x-delete-button :action="route('admin.backups.destroy', $backup['name'])" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<div class="mb-4 flex items-start gap-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
    <x-icon name="key" class="h-5 w-5 shrink-0" />
    <div>
        <p class="font-medium">{{ __('Gemini AI is not configured yet.') }}</p>
        <p>{{ __('Get a free API key at Google AI Studio and save it in Settings → Integrations.') }}
            @can('admin') <a href="{{ route('admin.settings.edit', 'integrations') }}" class="font-semibold underline">{{ __('Configure now') }}</a> @endcan
        </p>
    </div>
</div>

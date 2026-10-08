@php
    $messages = collect([
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('status') && ! in_array(session('status'), ['profile-updated', 'password-updated', 'verification-link-sent']) ? session('status') : null,
    ])->filter();
    $styles = [
        'success' => ['check-circle', 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30'],
        'error' => ['x-circle', 'bg-red-50 text-red-800 ring-red-200 dark:bg-red-500/10 dark:text-red-200 dark:ring-red-500/30'],
        'warning' => ['warning', 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30'],
        'info' => ['info', 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-500/30'],
    ];
@endphp
@foreach ($messages as $type => $message)
    <div x-data="{ show: true }" x-show="show" x-transition x-init="@if ($type === 'success') setTimeout(() => show = false, 6000) @endif"
         class="mb-4 flex items-start gap-3 rounded-lg p-3 text-sm ring-1 {{ $styles[$type][1] }}" role="alert">
        <x-icon :name="$styles[$type][0]" class="mt-0.5 h-5 w-5 shrink-0" />
        <div class="flex-1">{{ $message }}</div>
        <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="{{ __('Close') }}">
            <x-icon name="x" class="h-4 w-4" />
        </button>
    </div>
@endforeach
@if ($errors->any() && ! ($hideErrors ?? false))
    <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200 dark:bg-red-500/10 dark:text-red-200 dark:ring-red-500/30" role="alert">
        <p class="font-medium">{{ __('Please review the highlighted fields.') }}</p>
    </div>
@endif

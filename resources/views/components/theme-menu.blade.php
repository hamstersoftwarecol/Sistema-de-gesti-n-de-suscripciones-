@php
    $accents = ['indigo' => '#6366f1', 'blue' => '#3b82f6', 'emerald' => '#10b981', 'violet' => '#8b5cf6', 'rose' => '#f43f5e', 'amber' => '#f59e0b', 'teal' => '#14b8a6'];
    $surfaces = ['gray' => [__('Classic'), '#1f2937'], 'slate' => [__('Midnight'), '#1e293b'], 'zinc' => [__('Carbon'), '#27272a'], 'stone' => [__('Mocha'), '#292524']];
@endphp
<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" class="btn-icon" @click="open = !open" aria-label="{{ __('Appearance') }}">
        <span x-show="!$store.theme.isDark"><x-icon name="sun" /></span>
        <span x-show="$store.theme.isDark" x-cloak><x-icon name="moon" /></span>
    </button>
    <div x-show="open" x-transition x-cloak class="absolute right-0 z-50 mt-2 w-64 rounded-xl bg-white p-3 shadow-lg ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Mode') }}</p>
        <div class="grid grid-cols-3 gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-900">
            @foreach (['light' => ['sun', __('Light')], 'dark' => ['moon', __('Dark')], 'system' => ['desktop', __('System')]] as $mode => [$icon, $label])
                <button type="button" @click="$store.theme.set('mode', '{{ $mode }}')"
                        class="flex flex-col items-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300"
                        :class="$store.theme.mode === '{{ $mode }}' && 'bg-white shadow text-gray-900 dark:bg-gray-700 dark:text-white'">
                    <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
                </button>
            @endforeach
        </div>

        <p class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Accent color') }}</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($accents as $accent => $hex)
                <button type="button" @click="$store.theme.set('accent', '{{ $accent }}')" title="{{ ucfirst($accent) }}"
                        class="h-7 w-7 rounded-full ring-2 ring-offset-2 ring-offset-white transition dark:ring-offset-gray-800"
                        style="background: {{ $hex }}"
                        :class="$store.theme.accent === '{{ $accent }}' ? 'ring-gray-400' : 'ring-transparent'"></button>
            @endforeach
        </div>

        <p class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Dark theme') }}</p>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($surfaces as $surface => [$label, $hex])
                <button type="button" @click="$store.theme.set('surface', '{{ $surface }}')"
                        class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200 dark:text-gray-200 dark:ring-gray-700"
                        :class="$store.theme.surface === '{{ $surface }}' && 'ring-2 ring-primary-500 dark:ring-primary-500'">
                    <span class="h-4 w-4 rounded" style="background: {{ $hex }}"></span> {{ $label }}
                </button>
            @endforeach
        </div>
    </div>
</div>

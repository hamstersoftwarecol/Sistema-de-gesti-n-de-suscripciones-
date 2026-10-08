<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button type="button" class="btn-icon w-auto gap-1 px-2" @click="open = !open" aria-label="{{ __('Language') }}">
        <x-icon name="language" />
        <span class="text-xs font-semibold uppercase">{{ app()->getLocale() }}</span>
    </button>
    <div x-show="open" x-transition x-cloak class="absolute right-0 z-50 mt-2 w-40 overflow-hidden rounded-xl bg-white py-1 shadow-lg ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
        @foreach (available_locales() as $code => $name)
            <a href="{{ route('locale', $code) }}"
               @class(['flex items-center justify-between px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700', 'font-semibold text-primary-600 dark:text-primary-400' => app()->getLocale() === $code, 'text-gray-700 dark:text-gray-200' => app()->getLocale() !== $code])>
                {{ $name }}
                @if (app()->getLocale() === $code) <x-icon name="check" class="h-4 w-4" /> @endif
            </a>
        @endforeach
    </div>
</div>

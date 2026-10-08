<x-app-layout :title="__('Email templates')">
    <x-page-header :title="__('Email templates')" :subtitle="__('Messages sent automatically by the system')" />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($templates as $template)
            <a href="{{ route('admin.email-templates.edit', $template) }}" class="card block p-5 transition hover:ring-primary-300 dark:hover:ring-primary-700">
                <div class="flex items-start justify-between gap-2">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400"><x-icon name="envelope" /></span>
                    <x-status :value="(bool) $template->is_active" type="bool" />
                </div>
                <p class="mt-3 font-semibold">{{ $template->name }}</p>
                <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $template->subject }}</p>
                <p class="mt-2 font-mono text-xs text-gray-400">{{ $template->key }}</p>
            </a>
        @endforeach
    </div>
</x-app-layout>

@foreach ($messages as $message)
    @if ($message->role === 'user')
        <div class="flex justify-end">
            <div class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-sm bg-primary-600 px-4 py-2.5 text-sm text-white shadow-sm">{{ $message->content }}</div>
        </div>
    @else
        <div class="flex items-start gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-violet-500 text-white shadow">
                <x-icon name="sparkles" class="h-4 w-4" />
            </span>
            <div class="prose-ai min-w-0 max-w-[85%] overflow-x-auto rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm text-gray-700 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700">
                {!! $message->html() !!}
            </div>
        </div>
    @endif
@endforeach

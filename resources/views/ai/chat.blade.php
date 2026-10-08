<x-app-layout :title="__('AI assistant')">
    @php
        $suggestions = [
            __('Summarize the health of my business this month'),
            __('Which customers are at risk of cancelling and why?'),
            __('List overdue invoices and suggest a collection plan'),
            __('How can I increase my MRR in the next quarter?'),
            __('Write a friendly e-mail to recover a customer with overdue payments'),
            __('Explain how automatic renewals work in this system'),
        ];
    @endphp

    <x-page-header :title="__('AI assistant')" :subtitle="__('Powered by Google Gemini · knows your business metrics')">
        <x-slot:actions>
            <a href="{{ route('ai.insights') }}" class="btn-secondary"><x-icon name="chart" class="h-4 w-4" /> {{ __('Business analysis') }}</a>
            <form method="POST" action="{{ route('ai.conversations.store') }}">@csrf
                <button class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New conversation') }}</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @unless ($configured)
        @include('ai._not-configured')
    @endunless

    <div class="grid gap-6 lg:grid-cols-4">
        <aside class="card hidden h-fit lg:block">
            <div class="card-header"><h2 class="card-title text-sm">{{ __('Conversations') }}</h2></div>
            <ul class="max-h-[60vh] overflow-y-auto p-2">
                @forelse ($conversations as $item)
                    <li class="group flex items-center gap-1">
                        <a href="{{ route('ai.conversations.show', $item) }}"
                           @class(['nav-link flex-1 truncate', 'nav-link-active' => $conversation?->id === $item->id])>
                            <x-icon name="chat" class="h-4 w-4 shrink-0" /> <span class="truncate">{{ $item->title }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-3 py-2 text-sm text-gray-500">{{ __('No conversations yet.') }}</li>
                @endforelse
            </ul>
        </aside>

        <div class="lg:col-span-3">
            @if (! $conversation)
                <div class="card p-6 sm:p-10">
                    <div class="mx-auto max-w-2xl text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500 to-violet-500 text-white shadow-lg">
                            <x-icon name="sparkles" class="h-7 w-7" />
                        </span>
                        <h2 class="mt-4 text-xl font-bold">{{ __('How can I help you today?') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Ask about your customers, renewals, invoices or how to grow your recurring revenue.') }}</p>
                    </div>
                    <form method="POST" action="{{ route('ai.conversations.store') }}" class="mx-auto mt-6 max-w-2xl" x-data="{ prompt: '', loading: false }" @submit="loading = true">
                        @csrf
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($suggestions as $suggestion)
                                <button type="button" class="rounded-lg p-3 text-left text-sm text-gray-600 ring-1 ring-gray-200 transition hover:bg-gray-50 hover:ring-primary-300 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-800"
                                        @click="prompt = @js($suggestion); $nextTick(() => $refs.submit.click())">{{ $suggestion }}</button>
                            @endforeach
                        </div>
                        <div class="mt-4 flex gap-2">
                            <input type="text" name="prompt" x-model="prompt" class="input" placeholder="{{ __('Write your question...') }}" required @disabled(! $configured)>
                            <button x-ref="submit" class="btn-primary" :disabled="loading" @disabled(! $configured)>
                                <x-icon name="send" class="h-4 w-4" x-show="!loading" />
                                <svg x-show="loading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" class="opacity-75"/></svg>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card flex h-[calc(100vh-14rem)] min-h-[28rem] flex-col" x-data="aiChat(@js(route('ai.conversations.message', $conversation)))">
                    <div class="card-header">
                        <h2 class="card-title truncate" x-ref="title">{{ $conversation->title }}</h2>
                        <form method="POST" action="{{ route('ai.conversations.destroy', $conversation) }}" onsubmit="return confirm(@js(__('Delete this conversation?')))">
                            @csrf @method('DELETE')
                            <button class="btn-icon h-8 w-8" title="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4" /></button>
                        </form>
                    </div>

                    <div x-ref="messages" class="flex-1 space-y-4 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-950/40 sm:p-6">
                        @if ($conversation->messages->isEmpty())
                            <div class="grid gap-2 sm:grid-cols-2" x-show="!started">
                                @foreach ($suggestions as $suggestion)
                                    <button type="button" class="rounded-lg bg-white p-3 text-left text-sm text-gray-600 ring-1 ring-gray-200 hover:ring-primary-300 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700"
                                            @click="ask(@js($suggestion))">{{ $suggestion }}</button>
                                @endforeach
                            </div>
                        @endif
                        @include('ai._messages', ['messages' => $conversation->messages])
                    </div>

                    <template x-if="pending">
                        <div class="space-y-3 bg-gray-50 px-4 pb-4 dark:bg-gray-950/40 sm:px-6">
                            <div class="flex justify-end"><div class="max-w-[85%] whitespace-pre-line rounded-2xl rounded-br-sm bg-primary-600 px-4 py-2.5 text-sm text-white" x-text="pending"></div></div>
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-violet-500 text-white"><x-icon name="sparkles" class="h-4 w-4 animate-pulse" /></span>
                                {{ __('Gemini is thinking...') }}
                            </div>
                        </div>
                    </template>

                    <form @submit.prevent="send()" class="border-t border-gray-200 p-3 dark:border-gray-800">
                        <p x-show="error" x-text="error" x-cloak class="mb-2 text-sm text-red-600"></p>
                        <div class="flex items-end gap-2">
                            <textarea x-model="message" rows="1" class="input max-h-40 resize-none" placeholder="{{ __('Write your question...') }}"
                                      @keydown.enter.prevent="if (!$event.shiftKey) send(); else message += '\n'"
                                      @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'" @disabled(! $configured)></textarea>
                            <button class="btn-primary" :disabled="loading || !message.trim()" @disabled(! $configured)><x-icon name="send" class="h-4 w-4" /></button>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">{{ __('Enter to send · Shift+Enter for a new line · AI answers may contain mistakes.') }}</p>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

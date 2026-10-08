<x-app-layout :title="__('Business analysis')">
    @php
        $focuses = [
            'general' => [__('General overview'), 'chart'],
            'churn' => [__('Churn risk'), 'trending-down'],
            'revenue' => [__('Revenue & collections'), 'banknotes'],
            'pricing' => [__('Pricing & plans'), 'tag'],
            'sellers' => [__('Sales team'), 'briefcase'],
        ];
    @endphp

    <x-page-header :title="__('AI business analysis')" :subtitle="__('Gemini analyses your real metrics and suggests concrete actions.')" :back="route('ai.index')" />

    @unless ($configured)
        @include('ai._not-configured')
    @endunless

    <form method="POST" action="{{ route('ai.insights.generate') }}" class="card mb-6 p-4 sm:p-6" x-data="{ focus: @js($insight['focus'] ?? 'general'), loading: false }" @submit="loading = true">
        @csrf
        <p class="mb-3 text-sm font-medium">{{ __('What should the analysis focus on?') }}</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach ($focuses as $key => [$label, $icon])
                <label class="flex cursor-pointer flex-col items-center gap-2 rounded-lg p-3 text-center text-sm ring-1 ring-gray-200 transition dark:ring-gray-700"
                       :class="focus === '{{ $key }}' && 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-500/10'">
                    <input type="radio" name="focus" value="{{ $key }}" x-model="focus" class="sr-only">
                    <x-icon :name="$icon" class="h-5 w-5 text-primary-600 dark:text-primary-400" /> {{ $label }}
                </label>
            @endforeach
        </div>
        <div class="mt-4 flex justify-end">
            <button class="btn-primary" :disabled="loading" @disabled(! $configured)>
                <x-icon name="sparkles" class="h-4 w-4" />
                <span x-show="!loading">{{ __('Generate analysis') }}</span>
                <span x-show="loading" x-cloak>{{ __('Analyzing your data...') }}</span>
            </button>
        </div>
    </form>

    @if ($insight)
        <div class="card">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2"><x-icon name="sparkles" class="h-5 w-5 text-primary-500" /> {{ $focuses[$insight['focus']][0] ?? '' }}</h2>
                <span class="text-xs text-gray-500">{{ __('Generated :date', ['date' => fdate($insight['generated_at'], true)]) }}</span>
            </div>
            <div class="card-body prose-ai text-sm text-gray-700 dark:text-gray-300">
                {!! \Illuminate\Support\Str::markdown($insight['text'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
            </div>
        </div>
    @endif
</x-app-layout>

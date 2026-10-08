<x-app-layout :title="$template->name">
    <x-page-header :title="$template->name" :subtitle="$template->key" :back="route('admin.email-templates.index')" />

    <div class="grid gap-6 lg:grid-cols-2"
         x-data="{
             subject: @js(old('subject', $template->subject)),
             body: @js(old('body', $template->body)),
             preview: '', previewSubject: '', loading: false, aiLoading: false, error: null, instructions: '',
             async refresh() {
                 this.loading = true;
                 try {
                     const { data } = await axios.post(@js(route('admin.email-templates.preview', $template)), { subject: this.subject, body: this.body });
                     this.preview = data.html; this.previewSubject = data.subject;
                 } finally { this.loading = false; }
             },
             async draft() {
                 this.aiLoading = true; this.error = null;
                 try {
                     const { data } = await axios.post(@js(route('admin.email-templates.ai', $template)), { instructions: this.instructions });
                     this.body = data.body; this.refresh();
                 } catch (e) { this.error = e.response?.data?.message || e.message; }
                 finally { this.aiLoading = false; }
             },
             insert(tag) { this.body += tag; },
         }" x-init="refresh()">
        <form method="POST" action="{{ route('admin.email-templates.update', $template) }}" class="card">
            @csrf @method('PUT')
            <div class="card-body space-y-4">
                <x-forms.input name="name" :label="__('Name')" :value="$template->name" required />
                <div>
                    <label class="label" for="subject">{{ __('Subject') }}</label>
                    <input id="subject" name="subject" class="input" x-model="subject" @input.debounce.600ms="refresh()" required>
                </div>
                <div>
                    <label class="label" for="body">{{ __('Message') }}</label>
                    <textarea id="body" name="body" rows="12" class="input font-mono text-xs" x-model="body" @input.debounce.600ms="refresh()" required></textarea>
                    @error('body') <p class="input-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <p class="label">{{ __('Available placeholders (click to insert)') }}</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach (\App\Models\EmailTemplate::placeholders() as $placeholder)
                            <button type="button" class="badge bg-gray-100 font-mono text-gray-700 hover:bg-primary-100 dark:bg-gray-800 dark:text-gray-300" @click="insert(@js($placeholder))">{{ $placeholder }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-lg bg-primary-50 p-3 dark:bg-primary-500/10">
                    <p class="mb-2 flex items-center gap-2 text-sm font-medium text-primary-700 dark:text-primary-300"><x-icon name="sparkles" class="h-4 w-4" /> {{ __('Write it with Gemini AI') }}</p>
                    <div class="flex gap-2">
                        <input type="text" class="input" x-model="instructions" placeholder="{{ __('Optional instructions: tone, length, details...') }}">
                        <button type="button" class="btn-secondary" @click="draft()" :disabled="aiLoading">
                            <span x-show="!aiLoading">{{ __('Generate') }}</span><span x-show="aiLoading" x-cloak>...</span>
                        </button>
                    </div>
                    <p x-show="error" x-text="error" x-cloak class="mt-2 text-xs text-red-600"></p>
                </div>
                <x-forms.checkbox name="is_active" :label="__('Template enabled')" :checked="$template->is_active" />
            </div>
            <div class="flex justify-end border-t border-gray-200 p-4 dark:border-gray-800">
                <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save template') }}</button>
            </div>
        </form>

        <div class="card overflow-hidden">
            <div class="card-header"><h2 class="card-title">{{ __('Preview') }}</h2><span x-show="loading" class="text-xs text-gray-500">...</span></div>
            <div class="border-b border-gray-200 px-4 py-2 text-sm dark:border-gray-800"><span class="text-gray-500">{{ __('Subject') }}:</span> <span class="font-medium" x-text="previewSubject"></span></div>
            <iframe class="h-[560px] w-full bg-white" :srcdoc="preview" title="{{ __('Preview') }}"></iframe>
        </div>
    </div>
</x-app-layout>

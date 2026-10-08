<x-app-layout :title="__('Settings')">
    @php $s = fn ($key, $default = null) => $settings[$key] ?? $default; @endphp
    <x-page-header :title="__('Settings')" :subtitle="__('Configure your company, billing, e-mail and integrations')" />

    <div class="grid gap-6 lg:grid-cols-4">
        <nav class="card h-fit p-2">
            <div class="flex gap-1 overflow-x-auto lg:flex-col">
                @foreach ($tabs as $key => [$label, $icon])
                    <a href="{{ route('admin.settings.edit', $key) }}" @class(['nav-link shrink-0', 'nav-link-active' => $tab === $key])>
                        <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                    </a>
                @endforeach
                <a href="{{ route('admin.backups.index') }}" class="nav-link shrink-0"><x-icon name="database" class="h-5 w-5" /> {{ __('Backups') }}</a>
                <a href="{{ route('admin.email-templates.index') }}" class="nav-link shrink-0"><x-icon name="envelope" class="h-5 w-5" /> {{ __('Email templates') }}</a>
                <a href="{{ route('admin.custom-fields.index') }}" class="nav-link shrink-0"><x-icon name="puzzle" class="h-5 w-5" /> {{ __('Custom fields') }}</a>
            </div>
        </nav>

        <div class="space-y-6 lg:col-span-3">
            <form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="card">
                @csrf
                @method('PUT')
                <div class="card-header"><h2 class="card-title">{{ $tabs[$tab][0] }}</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    @switch($tab)
                        @case('general')
                            <x-forms.input name="company_name" :label="__('Company name')" :value="$s('company_name')" required />
                            <x-forms.input name="company_email" type="email" :label="__('Company e-mail')" :value="$s('company_email')" />
                            <x-forms.input name="company_phone" :label="__('Phone')" :value="$s('company_phone')" />
                            <x-forms.input name="company_tax_id" :label="__('Tax ID')" :value="$s('company_tax_id')" />
                            <x-forms.input name="company_address" :label="__('Address')" :value="$s('company_address')" class="sm:col-span-2" />
                            <x-forms.input name="company_website" type="url" :label="__('Website')" :value="$s('company_website')" />
                            <x-forms.select name="default_locale" :label="__('Default language')" :options="available_locales()" :value="$s('default_locale', 'es')" required />
                            <x-forms.select name="timezone" :label="__('Time zone')" :options="array_combine($timezones, $timezones)" :value="$s('timezone', 'UTC')" required />
                            <x-forms.select name="date_format" :label="__('Date format')" :value="$s('date_format', 'd/m/Y')" required
                                            :options="collect(['d/m/Y', 'm/d/Y', 'Y-m-d', 'd.m.Y', 'd-m-Y'])->mapWithKeys(fn ($f) => [$f => now()->format($f).'  ('.$f.')'])->all()" />
                            <div class="sm:col-span-2"><p class="label">{{ __('Default appearance for new users') }}</p></div>
                            <x-forms.select name="default_theme" :label="__('Mode')" :value="$s('default_theme', 'system')" required
                                            :options="['light' => __('Light'), 'dark' => __('Dark'), 'system' => __('System')]" />
                            <x-forms.select name="default_accent" :label="__('Accent color')" :value="$s('default_accent', 'indigo')" required
                                            :options="collect(['indigo', 'blue', 'emerald', 'violet', 'rose', 'amber', 'teal'])->mapWithKeys(fn ($c) => [$c => ucfirst($c)])->all()" />
                            <x-forms.select name="default_surface" :label="__('Dark theme')" :value="$s('default_surface', 'gray')" required
                                            :options="['gray' => __('Classic'), 'slate' => __('Midnight'), 'zinc' => __('Carbon'), 'stone' => __('Mocha')]" />
                            <x-forms.checkbox name="allow_registration" :label="__('Allow public registration')" :hint="__('New sign-ups become customers with access to the customer portal.')" :checked="(bool) $s('allow_registration')" class="sm:col-span-2" />
                            @break

                        @case('billing')
                            <x-forms.input name="invoice_prefix" :label="__('Invoice number prefix')" :value="$s('invoice_prefix', 'INV-')" required />
                            <x-forms.input name="invoice_next_number" type="number" min="1" :label="__('Next invoice number')" :value="$s('invoice_next_number', 1)" required />
                            <x-forms.input name="invoice_due_days" type="number" min="0" :label="__('Payment terms (days)')" :value="$s('invoice_due_days', 7)" required />
                            <x-forms.input name="default_tax_rate" type="number" step="0.01" min="0" max="100" :label="__('Default tax rate (%)')" :value="$s('default_tax_rate', 0)" required />
                            <x-forms.textarea name="invoice_notes" :label="__('Default invoice notes')" :value="$s('invoice_notes')" class="sm:col-span-2" :hint="__('E.g. bank account details for transfers.')" />
                            <x-forms.textarea name="invoice_terms" :label="__('Default terms & conditions')" :value="$s('invoice_terms')" class="sm:col-span-2" />
                            <x-forms.checkbox name="mark_past_due" :label="__('Mark subscriptions as past due when an invoice becomes overdue')" :checked="(bool) $s('mark_past_due', true)" class="sm:col-span-2" />
                            <x-forms.checkbox name="auto_email_invoices" :label="__('E-mail renewal invoices automatically (with PDF)')" :checked="(bool) $s('auto_email_invoices')" class="sm:col-span-2" />
                            @break

                        @case('reminders')
                            <x-forms.checkbox name="reminders_enabled" :label="__('Send automatic e-mail reminders')" :checked="(bool) $s('reminders_enabled', true)" class="sm:col-span-2" />
                            <x-forms.input name="reminder_days" :label="__('Days before renewal')" :value="$s('reminder_days', '7,3,1')" required :hint="__('Comma separated, e.g. 7,3,1')" />
                            <x-forms.input name="overdue_reminder_interval" type="number" min="1" :label="__('Repeat overdue notices every (days)')" :value="$s('overdue_reminder_interval', 3)" required />
                            @break

                        @case('mail')
                            <x-forms.select name="mail_mailer" :label="__('Mailer')" :value="$s('mail_mailer', 'smtp')" required
                                            :options="['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'log' => __('Log only (testing)')]" />
                            <x-forms.select name="mail_encryption" :label="__('Encryption')" :value="$s('mail_encryption', 'tls')" required
                                            :options="['tls' => 'TLS / STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => __('None')]" />
                            <x-forms.input name="mail_host" :label="__('SMTP host')" :value="$s('mail_host')" placeholder="smtp.gmail.com" />
                            <x-forms.input name="mail_port" type="number" :label="__('Port')" :value="$s('mail_port', 587)" />
                            <x-forms.input name="mail_username" :label="__('Username')" :value="$s('mail_username')" autocomplete="off" />
                            <x-forms.input name="mail_password" type="password" :label="__('Password')" autocomplete="new-password"
                                           :hint="$s('mail_password') ? __('A password is saved. Leave empty to keep it.') : null" />
                            <x-forms.input name="mail_from_address" type="email" :label="__('Sender e-mail')" :value="$s('mail_from_address', $s('company_email'))" required />
                            <x-forms.input name="mail_from_name" :label="__('Sender name')" :value="$s('mail_from_name', $s('company_name'))" required />
                            @break

                        @case('integrations')
                            <div class="sm:col-span-2 flex items-center gap-2 border-b border-gray-100 pb-2 dark:border-gray-800">
                                <x-icon name="sparkles" class="h-5 w-5 text-primary-500" /><h3 class="font-semibold">Google Gemini AI</h3>
                            </div>
                            <x-forms.input name="gemini_api_key" type="password" :label="__('API key')" autocomplete="off"
                                           :hint="$s('gemini_api_key') ? __('An API key is saved. Leave empty to keep it.') : __('Create one for free at aistudio.google.com/apikey')" />
                            <x-forms.input name="gemini_model" :label="__('Model')" :value="$s('gemini_model', 'gemini-2.5-flash')" required list="gemini-models" />
                            <datalist id="gemini-models">
                                <option value="gemini-2.5-flash"><option value="gemini-2.5-pro"><option value="gemini-2.5-flash-lite"><option value="gemini-2.0-flash">
                            </datalist>
                            @if ($s('gemini_api_key'))
                                <x-forms.checkbox name="clear_gemini_api_key" :label="__('Remove the saved API key')" class="sm:col-span-2" />
                            @endif

                            <div class="sm:col-span-2 mt-4 flex items-center gap-2 border-b border-gray-100 pb-2 dark:border-gray-800">
                                <x-icon name="globe" class="h-5 w-5 text-primary-500" /><h3 class="font-semibold">{{ __('Google sign-in (OAuth)') }}</h3>
                            </div>
                            <x-forms.checkbox name="google_login_enabled" :label="__('Enable “Sign in with Google”')" :checked="(bool) $s('google_login_enabled')" class="sm:col-span-2" />
                            <x-forms.input name="google_client_id" :label="__('Client ID')" :value="$s('google_client_id')" class="sm:col-span-2" />
                            <x-forms.input name="google_client_secret" type="password" :label="__('Client secret')" autocomplete="off"
                                           :hint="$s('google_client_secret') ? __('A secret is saved. Leave empty to keep it.') : null" />
                            <div>
                                <p class="label">{{ __('Authorized redirect URI') }}</p>
                                <code class="block break-all rounded-lg bg-gray-100 px-3 py-2 text-xs dark:bg-gray-800">{{ route('auth.google.callback') }}</code>
                            </div>
                            <x-forms.checkbox name="google_auto_register" :label="__('Create a customer account when an unknown Google user signs in')" :checked="(bool) $s('google_auto_register')" class="sm:col-span-2" />
                            @break

                        @case('maintenance')
                            <x-forms.checkbox name="maintenance_mode" :label="__('Enable maintenance mode')" :hint="__('Only administrators can use the application while it is enabled.')" :checked="(bool) $s('maintenance_mode')" class="sm:col-span-2" />
                            <x-forms.textarea name="maintenance_message" :label="__('Message for users')" :value="$s('maintenance_message')" class="sm:col-span-2" />
                            <x-forms.checkbox name="auto_backup" :label="__('Daily automatic database backup')" :checked="(bool) $s('auto_backup')" class="sm:col-span-2" />
                            <x-forms.input name="backup_keep" type="number" min="1" max="100" :label="__('Backups to keep')" :value="$s('backup_keep', 10)" required />
                            @break
                    @endswitch
                </div>
                <div class="flex justify-end border-t border-gray-200 p-4 dark:border-gray-800">
                    <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ __('Save settings') }}</button>
                </div>
            </form>

            @if ($tab === 'mail')
                <form method="POST" action="{{ route('admin.settings.test-mail') }}" class="card card-body flex flex-wrap items-end gap-3">
                    @csrf
                    <x-forms.input name="test_email" type="email" :label="__('Send a test e-mail to')" :value="auth()->user()->email" required class="min-w-[16rem] flex-1" />
                    <button class="btn-secondary"><x-icon name="send" class="h-4 w-4" /> {{ __('Send test') }}</button>
                </form>
            @endif

            @if ($tab === 'integrations' && $s('gemini_api_key'))
                <form method="POST" action="{{ route('admin.settings.test-gemini') }}" class="card card-body flex items-center justify-between gap-3">
                    @csrf
                    <p class="text-sm text-gray-500">{{ __('Check that the API key and model work.') }}</p>
                    <button class="btn-secondary"><x-icon name="sparkles" class="h-4 w-4" /> {{ __('Test Gemini connection') }}</button>
                </form>
            @endif

            @if ($tab === 'reminders')
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Scheduled tasks (cron)') }}</h2></div>
                    <div class="card-body space-y-4 text-sm">
                        <p class="text-gray-600 dark:text-gray-400">{{ __('The billing engine (renewals, invoices, expirations) and the e-mail reminders run once a day. Configure ONE of these options on your server:') }}</p>
                        <div>
                            <p class="label">1. {{ __('Server crontab (recommended)') }}</p>
                            <code class="block overflow-x-auto whitespace-nowrap rounded-lg bg-gray-900 px-3 py-2 text-xs text-gray-100">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
                        </div>
                        <div>
                            <p class="label">2. {{ __('Web cron URL (shared hosting / external services)') }}</p>
                            <code class="block break-all rounded-lg bg-gray-100 px-3 py-2 text-xs dark:bg-gray-800">{{ route('cron.run', $s('cron_token', '—')) }}</code>
                            <p class="hint">{{ __('Call it once a day (e.g. with cron-job.org). Keep it secret.') }}</p>
                        </div>
                        <dl class="grid gap-2 sm:grid-cols-3">
                            <div><dt class="text-xs text-gray-500">{{ __('Last billing run') }}</dt><dd class="font-medium">{{ $s('last_billing_run') ? fdate($s('last_billing_run'), true) : __('Never') }}</dd></div>
                            <div><dt class="text-xs text-gray-500">{{ __('Last reminders run') }}</dt><dd class="font-medium">{{ $s('last_reminder_run') ? fdate($s('last_reminder_run'), true) : __('Never') }}</dd></div>
                            <div><dt class="text-xs text-gray-500">{{ __('Last web cron') }}</dt><dd class="font-medium">{{ $s('last_cron_run') ? fdate($s('last_cron_run'), true) : __('Never') }}</dd></div>
                        </dl>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.settings.run-cron') }}">@csrf
                                <button class="btn-primary"><x-icon name="play" class="h-4 w-4" /> {{ __('Run now') }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.settings.cron-token') }}" onsubmit="return confirm(@js(__('The current URL will stop working. Continue?')))">@csrf
                                <button class="btn-secondary"><x-icon name="refresh" class="h-4 w-4" /> {{ __('Regenerate URL') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

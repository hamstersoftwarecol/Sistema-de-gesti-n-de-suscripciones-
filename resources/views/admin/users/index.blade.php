<x-app-layout :title="__('Users')">
    <x-page-header :title="__('Users')" :subtitle="__('Administrators, staff, sellers and portal customers')">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New user') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="flex flex-wrap gap-3 border-b border-gray-200 p-4 dark:border-gray-800">
            <input type="search" name="q" value="{{ request('q') }}" class="input min-w-[12rem] flex-1" placeholder="{{ __('Name or e-mail') }}">
            <select name="role" class="input w-auto">
                <option value="">{{ __('All roles') }}</option>
                @foreach (\App\Models\User::roleOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-secondary"><x-icon name="funnel" class="h-4 w-4" /> {{ __('Filter') }}</button>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>{{ __('User') }}</th><th>{{ __('Role') }}</th><th>{{ __('Linked to') }}</th><th>{{ __('Last login') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">{{ $user->initials() }}</span>
                                    <div><p class="font-medium">{{ $user->name }}</p><p class="text-xs text-gray-500">{{ $user->email }} @if ($user->google_id) · Google @endif</p></div>
                                </div>
                            </td>
                            <td>{{ \App\Models\User::roleOptions()[$user->role] ?? $user->role }}</td>
                            <td>
                                @if ($user->customer) <a href="{{ route('customers.show', $user->customer) }}" class="link">{{ $user->customer->name }}</a>
                                @elseif ($user->seller) <a href="{{ route('sellers.show', $user->seller) }}" class="link">{{ $user->seller->name }}</a>
                                @else — @endif
                            </td>
                            <td>{{ $user->last_login_at ? fdate($user->last_login_at, true) : __('Never') }}</td>
                            <td><x-status :value="(bool) $user->is_active" type="bool" /></td>
                            <td class="text-right">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn-icon h-8 w-8"><x-icon name="pencil" class="h-4 w-4" /></a>
                                @unless ($user->is(auth()->user()))
                                    <x-delete-button :action="route('admin.users.destroy', $user)" />
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 p-4 dark:border-gray-800">{{ $users->links() }}</div>
    </div>
</x-app-layout>

<x-dynamic-component :component="auth()->user()->isCustomer() ? 'portal-layout' : 'app-layout'" :title="__('Profile')">
    <x-page-header :title="__('Profile')" :subtitle="__('Your account, password and preferences')" />

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card card-body">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="card card-body">
            @include('profile.partials.update-password-form')
        </div>
        <div class="card card-body lg:col-span-2">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-dynamic-component>

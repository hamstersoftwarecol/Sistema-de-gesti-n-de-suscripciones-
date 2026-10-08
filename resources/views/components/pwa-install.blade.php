<button type="button" x-data="{ available: !!window.deferredInstallPrompt }" x-show="available" x-cloak
        @pwa-installable.window="available = true"
        @click="window.deferredInstallPrompt?.prompt(); window.deferredInstallPrompt = null; available = false"
        class="btn-icon" title="{{ __('Install app') }}" aria-label="{{ __('Install app') }}">
    <x-icon name="mobile" />
</button>

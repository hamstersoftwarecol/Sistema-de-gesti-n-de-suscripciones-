import './bootstrap';

import Alpine from 'alpinejs';
import theme from './theme';
import chart from './components/chart';
import kanban from './components/kanban';
import calendar from './components/calendar';
import invoiceForm from './components/invoice-form';
import aiChat from './components/ai-chat';

window.Alpine = Alpine;

Alpine.store('theme', theme);
Alpine.data('chart', chart);
Alpine.data('kanban', kanban);
Alpine.data('calendar', calendar);
Alpine.data('invoiceForm', invoiceForm);
Alpine.data('aiChat', aiChat);

Alpine.start();

// Progressive Web App: offline support and "install app" prompt.
if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('pwa-installable'));
});

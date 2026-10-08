/**
 * Appearance store: light/dark/system mode, accent colour and surface (dark theme) palette.
 * Preferences live in localStorage and are synced to the user profile when signed in.
 */
const media = window.matchMedia('(prefers-color-scheme: dark)');
const root = document.documentElement;

function read(key, fallback) {
    try {
        return localStorage.getItem(key) || fallback;
    } catch (e) {
        return fallback;
    }
}

function write(key, value) {
    try {
        localStorage.setItem(key, value);
    } catch (e) {
        /* storage may be unavailable (private mode) */
    }
}

export default {
    mode: read('theme', root.dataset.theme || 'system'),
    accent: read('accent', root.dataset.accent || 'indigo'),
    surface: read('surface', root.dataset.surface || 'gray'),

    init() {
        this.apply();
        media.addEventListener('change', () => this.mode === 'system' && this.apply());
    },

    get isDark() {
        return this.mode === 'dark' || (this.mode === 'system' && media.matches);
    },

    apply() {
        root.classList.toggle('dark', this.isDark);
        root.dataset.accent = this.accent;
        root.dataset.surface = this.surface;
        window.dispatchEvent(new CustomEvent('theme-changed'));
    },

    set(key, value) {
        this[key] = value;
        write(key === 'mode' ? 'theme' : key, value);
        this.apply();
        this.sync();
    },

    toggle() {
        this.set('mode', this.isDark ? 'light' : 'dark');
    },

    sync() {
        const url = document.querySelector('meta[name="preferences-url"]')?.content;
        if (!url) return;
        window.axios.post(url, { theme: this.mode, accent: this.accent, surface: this.surface }).catch(() => {});
    },
};

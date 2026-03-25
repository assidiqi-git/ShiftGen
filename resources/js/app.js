import html2canvas from 'html2canvas';
import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

window.html2canvas = html2canvas;
window.Toastify = Toastify;

const SIDEBAR_OPEN_CLASS = 'sidebar-open';

const isDesktopLayout = () => window.matchMedia('(min-width: 1024px)').matches;

const closeSidebar = () => {
    document.body.classList.remove(SIDEBAR_OPEN_CLASS);
};

const toggleSidebar = () => {
    document.body.classList.toggle(SIDEBAR_OPEN_CLASS);
};

document.addEventListener('click', (event) => {
    const toggleEl = event.target.closest('[data-sidebar-toggle]');
    if (toggleEl) {
        event.preventDefault();
        if (!isDesktopLayout()) toggleSidebar();
        return;
    }

    const backdropEl = event.target.closest('[data-sidebar-backdrop]');
    if (backdropEl) {
        closeSidebar();
        return;
    }

    const closeEl = event.target.closest('[data-sidebar-close]');
    if (closeEl && !isDesktopLayout()) {
        closeSidebar();
    }
});

window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeSidebar();
});

window.addEventListener('resize', () => {
    if (isDesktopLayout()) closeSidebar();
});

const THEME_KEY = 'sg-theme';
const ICON_SUN = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>';
const ICON_MOON = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>';

const getPreferredTheme = () => {
    const saved = localStorage.getItem(THEME_KEY);
    if (saved === 'dark' || saved === 'light') return saved;
    return 'light';
};

const applyTheme = (theme) => {
    const root = document.documentElement;
    if (theme === 'dark') root.classList.add('dark');
    else root.classList.remove('dark');
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        btn.innerHTML = theme === 'dark' ? ICON_SUN : ICON_MOON;
        btn.title = theme === 'dark' ? 'Mode Terang' : 'Mode Gelap';
    });
};

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(getPreferredTheme());
});

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    e.preventDefault();
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next);
});

const normalizeToastPayload = (payload) => {
    if (!payload) return null;
    if (typeof payload === 'string') return { message: payload, type: 'info' };
    if (typeof payload === 'object') {
        const message = payload.message ?? payload.text ?? '';
        const type = payload.type ?? payload.status ?? (payload.success ? 'success' : 'info');
        return { message, type };
    }
    return null;
};

const showToast = (payload) => {
    if (!window.Toastify) return;
    const p = normalizeToastPayload(payload);
    if (!p || !p.message) return;
    const isSuccess = p.type === 'success' || p.type === 'ok' || p.type === 'info';
    window.Toastify({
        text: p.message,
        duration: 4000,
        close: true,
        gravity: 'top',
        position: 'right',
        stopOnFocus: true,
        style: {
            background: isSuccess
                ? 'linear-gradient(to right, #16a34a, #22c55e)'
                : 'linear-gradient(to right, #dc2626, #ef4444)',
        },
    }).showToast();
};

document.addEventListener('alpine:init', () => {
    if (!window.Alpine) return;
    const store = window.Alpine.store('toast');
    if (store) {
        store.show = showToast;
    } else {
        window.Alpine.store('toast', { show: showToast });
    }
});

window.addEventListener('toast-show', (e) => {
    const detail = e?.detail ?? e;
    showToast(detail);
});

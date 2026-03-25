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
const ICON_SUN = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.8 1.42-1.42zm10.45 14.32l1.79 1.8 1.41-1.41-1.8-1.79-1.4 1.4zM12 4V1h-0v3h0zm0 19v-3h-0v3h0zM4 13H1v-0h3v0zm22 0h-3v-0h3v0zM6.76 19.16l-1.42 1.42-1.79-1.8 1.41-1.41 1.8 1.79zM18.36 4.84l1.4-1.4 1.8 1.79-1.41 1.41-1.79-1.8zM12 7a5 5 0 100 10 5 5 0 000-10z"/></svg>';
const ICON_MOON = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.01 2a9.99 9.99 0 00-8.9 14.59A10 10 0 1012.01 2zm0 2c.23 0 .46.01.69.03A8 8 0 0110 20a8 8 0 01-6.06-12.94A10 10 0 0012.01 4z"/></svg>';

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

/**
 * Dark Mode Toggle — Kasama Support Hub
 * - Reads/writes localStorage for persistence
 * - Respects OS-level prefers-color-scheme on first visit
 * - Injects toggle button into the navigation
 * - Applies theme immediately on load to prevent FOUC
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'kasama-theme';
    var ATTR = 'data-theme';
    var html = document.documentElement;

    /* ── Apply saved preference immediately (prevents flash) ── */
    var saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) {
        saved = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }
    html.setAttribute(ATTR, saved);

    /* ── Build toggle button ── */
    function buildButton() {
        var btn = document.createElement('button');
        btn.id = 'dm-toggle';
        btn.className = 'dm-toggle-btn';
        btn.type = 'button';
        btn.title = 'Toggle dark / light mode';
        btn.setAttribute('aria-label', 'Toggle dark mode');
        return btn;
    }

    /* ── Moon SVG (show in light mode → click to go dark) ── */
    var moonSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';

    /* ── Sun SVG (show in dark mode → click to go light) ── */
    var sunSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';

    /* ── Update button appearance to reflect current theme ── */
    function refreshButton(btn) {
        var isDark = html.getAttribute(ATTR) === 'dark';
        btn.innerHTML = isDark ? sunSVG : moonSVG;
        btn.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
    }

    /* ── Toggle handler ── */
    function handleToggle(btn) {
        var isDark = html.getAttribute(ATTR) === 'dark';
        var next = isDark ? 'light' : 'dark';
        html.setAttribute(ATTR, next);
        localStorage.setItem(STORAGE_KEY, next);
        refreshButton(btn);
    }

    /* ── Inject button into DOM ── */
    function injectButton() {
        /* Avoid duplicate injection */
        if (document.getElementById('dm-toggle')) { return; }

        var btn = buildButton();
        refreshButton(btn);

        btn.addEventListener('click', function () { handleToggle(btn); });

        /* 1. Try .nav-right (most pages) */
        var navRight = document.querySelector('.nav-right');
        if (navRight) {
            navRight.insertBefore(btn, navRight.firstChild);
            return;
        }

        /* 2. Try .header-right (secondary header bar — typically light bg) */
        var headerRight = document.querySelector('.header-right');
        if (headerRight) {
            /* dashboard-header has a white background, so use dark-colored button */
            if (headerRight.closest('.dashboard-header, header')) {
                btn.classList.add('dm-light-nav');
            }
            headerRight.insertBefore(btn, headerRight.firstChild);
            return;
        }

        /* 3. Fallback: floating button */
        btn.classList.add('dm-floating');
        document.body.appendChild(btn);
    }

    /* ── Wait for DOM, then inject ── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectButton);
    } else {
        injectButton();
    }

    /* ── Sync across tabs ── */
    window.addEventListener('storage', function (e) {
        if (e.key === STORAGE_KEY && e.newValue) {
            html.setAttribute(ATTR, e.newValue);
            var btn = document.getElementById('dm-toggle');
            if (btn) { refreshButton(btn); }
        }
    });

})();

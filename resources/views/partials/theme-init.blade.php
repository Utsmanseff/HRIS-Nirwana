{{-- Dark/light theme bootstrap. MUST run in <head> before paint (no flash).
     Ported from docs/mockups/assets/theme-toggle.js. --}}
<script>
(function () {
    var KEY = 'nirwana-theme';
    var root = document.documentElement;
    var saved = localStorage.getItem(KEY);
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    root.dataset.theme = saved || (prefersDark ? 'dark' : 'light');

    var SUN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
    var MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>';
    function icon() { return root.dataset.theme === 'dark' ? SUN : MOON; }

    window.toggleTheme = function () {
        var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = next;
        localStorage.setItem(KEY, next);
        document.querySelectorAll('[data-theme-icon]').forEach(function (el) { el.innerHTML = icon(); });
    };
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-theme-icon]').forEach(function (el) { el.innerHTML = icon(); });
    });
})();
</script>

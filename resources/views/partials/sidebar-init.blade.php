{{-- Sidebar collapsed/expanded state bootstrap. MUST run in <head> before paint (no flash).
     Mirrors theme-init.blade.php's pattern: read localStorage synchronously and set a
     data attribute on <html> so CSS can key off it before Alpine (loaded via
     @livewireScripts at the end of <body>) has even parsed. --}}
<script>
(function () {
    var KEY = 'nirwana-sidebar';
    var root = document.documentElement;
    var saved = localStorage.getItem(KEY);
    root.dataset.sidebar = saved === 'collapsed' ? 'collapsed' : 'expanded';
})();
</script>

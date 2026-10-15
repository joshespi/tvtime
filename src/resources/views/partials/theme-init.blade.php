{{-- The media pair is what a no-JS visit gets; syncThemeColor() below takes over otherwise. --}}
<meta name="theme-color" content="#f9fafb" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">
<script>
    (function () {
        // localStorage throws in some private-browsing / site-data-blocked
        // configurations rather than just returning null — this must not be
        // the thing that crashes page load in the head.
        var stored = null;
        try {
            stored = localStorage.getItem('theme');
        } catch (e) {}
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (stored === 'dark' || (!stored && prefersDark)) {
            document.documentElement.classList.add('dark');
        }

        // Keeps the browser-chrome colour in step with the active theme, including
        // a manual toggle that disagrees with the OS setting. Called again by the toggle.
        window.syncThemeColor = function () {
            var dark = document.documentElement.classList.contains('dark');
            var color = dark ? '#111827' : '#f9fafb';
            document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
                button.setAttribute('aria-pressed', dark ? 'true' : 'false');
            });
            document.querySelectorAll('meta[name="theme-color"]').forEach(function (meta) {
                meta.setAttribute('content', color);
            });
        };
        document.addEventListener('DOMContentLoaded', window.syncThemeColor);

        window.toggleTheme = function () {
            var dark = document.documentElement.classList.toggle('dark');
            try {
                localStorage.setItem('theme', dark ? 'dark' : 'light');
            } catch (e) {}
            window.syncThemeColor();
        };
    })();
</script>

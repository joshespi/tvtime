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
    })();
</script>

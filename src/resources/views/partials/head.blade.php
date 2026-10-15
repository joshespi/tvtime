<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ config('app.name', 'Laravel') }}</title>

@include('partials.icons')

<link rel="manifest" href="{{ route('manifest') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'TV Tracker') }}">

@include('partials.theme-init')

<!-- Scripts -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        });
    }
</script>

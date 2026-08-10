{
    "name": "{{ config('app.name', 'TV Tracker') }}",
    "short_name": "{{ config('app.name', 'TV Tracker') }}",
    "description": "Track what you're watching.",
    "start_url": "{{ route('dashboard') }}",
    "scope": "/",
    "display": "standalone",
    "background_color": "#111827",
    "theme_color": "#111827",
    "icons": [
        {
            "src": "{{ asset('icon-192.png') }}",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any"
        },
        {
            "src": "{{ asset('icon-192.png') }}",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "maskable"
        },
        {
            "src": "{{ asset('icon-512.png') }}",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "any"
        },
        {
            "src": "{{ asset('icon-512.png') }}",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "maskable"
        }
    ]
}

{
    "name": @json(config('app.name', 'TV Tracker')),
    "short_name": @json(config('app.name', 'TV Tracker')),
    "description": "Track what you're watching.",
    "start_url": "{{ route('dashboard') }}",
    "scope": "/",
    "display": "standalone",
    "background_color": "{{ config('app.theme_colors.dark') }}",
    "theme_color": "{{ config('app.theme_colors.dark') }}",
    "icons": [
        {
            "src": "{{ asset('icon-192.png') }}",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any"
        },
        {
            "src": "{{ asset('icon-maskable-192.png') }}",
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
            "src": "{{ asset('icon-maskable-512.png') }}",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "maskable"
        }
    ]
}

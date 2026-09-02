// A fetch handler is the one thing Chrome requires for installability, so
// this exists to earn the install prompt. It deliberately does no caching
// (network passthrough only) so it can't serve a stale session-guarded page,
// stale CSRF token, or stale build asset. Offline support is a future
// enhancement, not attempted here.

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Navigations (i.e. clicking a link) are deliberately left alone rather than
    // re-fetched via event.request — re-fetching a navigation whose response is a
    // redirect (Laravel's auth/session redirects, etc.) can hand the browser a
    // response it can't use to satisfy that navigation. In a normal tab that just
    // shows a failed-navigation error page, but an installed Android PWA has no
    // chrome to fall back into and force-closes instead. Since this worker does no
    // caching anyway, there's nothing gained by intercepting navigations at all.
    if (event.request.mode === 'navigate') {
        return;
    }

    event.respondWith(fetch(event.request));
});

// Payload shape comes from NotificationChannels\WebPush\WebPushMessage::toArray()
// server-side — title/body/icon/tag/data, whichever of those the notification set.
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const payload = event.data.json();

    event.waitUntil(
        self.registration.showNotification(payload.title || 'TV Tracker', {
            body: payload.body,
            icon: payload.icon || '/icon-192.png',
            badge: payload.badge,
            tag: payload.tag,
            data: payload.data,
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = (event.notification.data && event.notification.data.url) || '/dashboard';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        })
    );
});

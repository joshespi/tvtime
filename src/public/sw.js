// No fetch handler here on purpose. Chrome hasn't required one for installability
// for years now (a valid manifest + a registered SW with install/activate is
// enough) — so we don't register one at all. On Android, an installed PWA's
// service worker gets fully terminated after ~30s of inactivity, and having a
// fetch handler means the *next* navigation (any link click) has to wake that
// terminated worker back up to run it. That wake-on-navigate path is a known
// crash trigger specifically in the WebAPK/standalone host (not a normal Chrome
// tab, which recovers from it fine) — matches the exact bug seen here: works all
// session, breaks the first click after the app sits closed long enough for the
// worker to go idle. This worker does no caching anyway, so intercepting fetch
// bought nothing.

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Payload shape comes from NotificationChannels\WebPush\WebPushMessage::toArray()
// server-side — title/body/icon/tag/data, whichever of those the notification set.
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload;
    try {
        payload = event.data.json();
    } catch (error) {
        return;
    }

    event.waitUntil(
        self.registration.showNotification(payload.title || 'TV Tracker', {
            body: payload.body,
            icon: payload.icon || '/icon-192.png',
            badge: payload.badge || '/icon-192.png',
            tag: payload.tag,
            data: payload.data,
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = (event.notification.data && event.notification.data.url) || '/dashboard';
    // Resolved to an absolute URL up front: client.url below is always absolute
    // (e.g. "https://host/dashboard"), so comparing it against a bare path like
    // "/dashboard" never matched and this always fell through to opening a new
    // window instead of focusing the already-open app.
    const targetUrl = new URL(target, self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});

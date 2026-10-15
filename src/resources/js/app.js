import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Debounced typeahead against /search/suggest; selecting a suggestion just fills
// the box and submits the normal GET search rather than tracking anything itself.
Alpine.data('catalogSearch', (suggestUrl) => ({
    query: '',
    suggestions: [],
    open: false,
    highlighted: -1,
    debounceTimer: null,
    abortController: null,

    init() {
        this.query = this.$refs.input.value;
    },

    onInput() {
        this.highlighted = -1;
        clearTimeout(this.debounceTimer);

        if (this.query.trim().length < 2) {
            this.suggestions = [];
            this.open = false;
            return;
        }

        this.debounceTimer = setTimeout(() => this.fetchSuggestions(), 250);
    },

    async fetchSuggestions() {
        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();

        try {
            const response = await fetch(`${suggestUrl}?q=${encodeURIComponent(this.query)}`, {
                headers: { Accept: 'application/json' },
                signal: this.abortController.signal,
            });

            this.suggestions = response.ok ? await response.json() : [];
            this.open = this.suggestions.length > 0;
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.suggestions = [];
                this.open = false;
            }
        }
    },

    select(suggestion) {
        this.query = suggestion.title;
        this.open = false;
        this.$nextTick(() => this.$refs.form.requestSubmit());
    },

    move(delta) {
        if (! this.open || this.suggestions.length === 0) {
            return;
        }

        this.highlighted = (this.highlighted + delta + this.suggestions.length) % this.suggestions.length;
    },

    chooseHighlighted(event) {
        if (this.highlighted >= 0 && this.suggestions[this.highlighted]) {
            event.preventDefault();
            this.select(this.suggestions[this.highlighted]);
        } else {
            this.open = false;
        }
    },

    close() {
        this.open = false;
        this.highlighted = -1;
    },
}));

// Backs the Track/Add-to-watchlist button on search result cards. Submits via
// fetch instead of a normal form POST so picking a result doesn't navigate the
// user away from their search — they can track several results in a row.
Alpine.data('trackButton', (storeUrl, type, externalId) => ({
    status: 'idle', // idle | loading | done | error
    message: '',

    async track() {
        if (this.status === 'loading' || this.status === 'done') {
            return;
        }

        this.status = 'loading';
        this.message = '';

        try {
            const response = await sendJson(storeUrl, { type, external_id: externalId });

            if (response.status === 419) {
                this.status = 'error';
                this.message = 'Session expired — reload the page.';
                return;
            }

            const data = await response.json().catch(() => ({}));

            if (! response.ok) {
                this.status = 'error';
                this.message = data.message || 'Something went wrong.';
                return;
            }

            // No success message: the button's own "✓ Tracking" state says it,
            // and `message` is only ever rendered on the error branch.
            this.status = 'done';
            this.$dispatch('tracked');
        } catch (error) {
            this.status = 'error';
            this.message = 'Something went wrong.';
        }
    },
}));

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);

    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// Every write this file makes wants the same JSON + CSRF envelope; the token is
// read per call rather than captured, so a page live long enough to be issued a
// fresh one doesn't keep posting the stale value.
function sendJson(url, body, method = 'POST') {
    return fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
        },
        body: JSON.stringify(body),
    });
}

// Subscribing is entirely a browser API (Notification.requestPermission + PushManager);
// the backend only comes in once there's a subscription object to persist.
Alpine.data('pushNotifications', (vapidPublicKey, subscribeUrl, unsubscribeUrl) => ({
    supported: true,
    subscribed: false,
    loading: false,
    error: null,

    async init() {
        this.supported = 'serviceWorker' in navigator && 'PushManager' in window;

        if (! this.supported) {
            return;
        }

        try {
            // ready never settles when no service worker is registered, so cap the wait.
            const registration = await Promise.race([
                navigator.serviceWorker.ready,
                new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 5000)),
            ]);
            this.subscribed = (await registration.pushManager.getSubscription()) !== null;
        } catch (error) {
            this.supported = false;
        }
    },

    async toggle() {
        this.loading = true;
        this.error = null;

        try {
            if (this.subscribed) {
                await this.unsubscribe();
            } else {
                await this.subscribe();
            }
        } catch (error) {
            this.error = error.message || 'Something went wrong.';
        } finally {
            this.loading = false;
        }
    },

    async subscribe() {
        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            this.error = 'Notification permission was not granted.';
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });

        const response = await sendJson(subscribeUrl, subscription.toJSON());

        if (! response.ok) {
            await subscription.unsubscribe();
            throw new Error('Could not save the subscription. Reload and try again.');
        }

        this.subscribed = true;
    },

    async unsubscribe() {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (! subscription) {
            this.subscribed = false;
            return;
        }

        const endpoint = subscription.endpoint;
        await subscription.unsubscribe();

        const response = await sendJson(unsubscribeUrl, { endpoint }, 'DELETE');

        if (! response.ok) {
            throw new Error('Could not remove the subscription. Reload and try again.');
        }

        this.subscribed = false;
    },
}));

// External links deliberately carry no target in the markup. An installed app
// window has nowhere to put a new tab, and asking for one takes the app down
// with it: Chrome's Android WebAPK finishes the activity (you land back on the
// home screen, task still sitting in recents), iOS standalone does the same,
// Firefox prompts. So only a real browser tab gets target="_blank", and the
// decision is made at click time rather than on load, which also covers links
// Alpine renders later.
//
// Note the polarity. The previous version of this did the reverse — strip the
// target when standalone is *detected* — and it never once fired, because the
// installed app doesn't report `display-mode: standalone` here. Deciding it this
// way round means a browser that misreports its display mode falls through to
// navigating in place (slightly worse UX) instead of to the variant that closes
// the app.
function inBrowserTab() {
    const installedModes = ['standalone', 'minimal-ui', 'fullscreen', 'window-controls-overlay'];

    // Require a positive match on an installed mode rather than a positive match on
    // "browser" — a browser that doesn't report display-mode at all (or misreports
    // it) then defaults to opening a new tab, which is the safe failure: worst case
    // a real installed app misses the fix, instead of every normal browser losing
    // target="_blank" on external links.
    const isInstalled = installedModes.some((mode) => window.matchMedia(`(display-mode: ${mode})`).matches)
        || window.navigator.standalone === true;

    return ! isInstalled;
}

document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');

    if (! link || ! link.hostname || link.origin === location.origin) {
        return;
    }

    if (inBrowserTab()) {
        link.target = '_blank';
    } else {
        link.removeAttribute('target');
    }
}, true);

// Stops a double-tap from posting a form twice: once a form is on its way, its
// submit buttons go dead. Runs in the bubble phase, so a form whose own handler
// cancelled the submit (a declined confirm(), an Alpine @submit.prevent) is left
// alone. pageshow re-arms the buttons when the browser restores the page from
// its back/forward cache with them still disabled.
document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) {
        return;
    }

    const form = event.target;
    // Deferred so the browser has already read the form (and the clicked
    // button's own name/value) before anything gets disabled.
    setTimeout(() => {
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            button.disabled = true;
            button.dataset.submitting = '';
            button.classList.add('opacity-60', 'cursor-wait');
        });
    }, 0);
});

window.addEventListener('pageshow', (event) => {
    if (!event.persisted) {
        return;
    }

    document.querySelectorAll('form button[data-submitting]').forEach((button) => {
        button.disabled = false;
        delete button.dataset.submitting;
        button.classList.remove('opacity-60', 'cursor-wait');
    });
});

// Wired to the per-episode watched forms on the show page: confirm→mark everything
// before it too, decline→mark just this episode.
window.tvTracker = {
    confirmMarkWatched(form) {
        if (confirm('Mark the unwatched episodes before this one as watched too?')) {
            form.action = form.dataset.markThroughUrl;
        }
        return true;
    },
};

Alpine.start();

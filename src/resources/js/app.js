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
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ type, external_id: externalId }),
            });

            const data = await response.json();

            if (! response.ok) {
                this.status = 'error';
                this.message = data.message || 'Something went wrong.';
                return;
            }

            this.status = 'done';
            this.message = data.message;
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
    return document.querySelector('meta[name="csrf-token"]').content;
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

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        this.subscribed = subscription !== null;
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

        await fetch(subscribeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify(subscription.toJSON()),
        });

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

        await fetch(unsubscribeUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ endpoint }),
        });

        this.subscribed = false;
    },
}));

Alpine.start();

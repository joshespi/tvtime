<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Push Notifications') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Get a browser notification when a show you're tracking has a new episode air.") }}
        </p>
    </header>

    @if (! config('webpush.vapid.public_key'))
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('Push notifications are not configured on this server.') }}</p>
    @else
    <div class="mt-4"
         x-data="pushNotifications(@js(config('webpush.vapid.public_key')), @js(route('push-subscriptions.store')), @js(route('push-subscriptions.destroy')))">
        <template x-if="!supported">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Not supported in this browser. On iPhone/iPad, add this app to your Home Screen first (Safari share menu → Add to Home Screen), then try again from there.') }}
            </p>
        </template>

        <template x-if="supported">
            <div class="flex items-center gap-4">
                <button type="button" @click="toggle()" :disabled="loading"
                        class="{{ \App\Support\ButtonStyle::base() }}"
                        :class="subscribed ? @js(\App\Support\ButtonStyle::variant('secondary')) : @js(\App\Support\ButtonStyle::variant('primary'))">
                    <span x-show="!loading" x-text="subscribed ? 'Disable notifications' : 'Enable notifications'"></span>
                    <span x-show="loading">Working&hellip;</span>
                </button>
                <p x-show="error" x-text="error" class="text-sm text-red-600 dark:text-red-400"></p>
            </div>
        </template>
    </div>
    @endif
</section>

<x-app-layout title="Profile">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card padding="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </x-card>

            <x-card padding="p-4 sm:p-8">
                <div class="max-w-xl flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Your Stats</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">See what you've watched, your streaks, and top genres.</p>
                    </div>
                    <x-action-button variant="primary" :href="route('profile.stats')" class="shrink-0">View stats</x-action-button>
                </div>
            </x-card>

            <x-card padding="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-notifications-form')
                </div>
            </x-card>

            <x-card padding="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-recommendations-form')
                </div>
            </x-card>

            <x-card padding="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </x-card>

            <x-card padding="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        // App\Support\ButtonStyle holds the button palette as PHP constants.
        './app/Support/*.php',
    ],

    theme: {
        extend: {
            colors: {
                success: '#087941',
                // gray-50/900 mirror config('app.theme_colors') — change both together.
                gray: {
                    50: '#f8fbff',
                    800: '#192339',
                    900: '#10182b',
                },
                indigo: {
                    50: '#eff5ff',
                    100: '#deebff',
                    300: '#a9c5ff',
                    400: '#8ab1ff',
                    500: '#2464e8',
                    600: '#0949c5',
                    700: '#0c3da4',
                    800: '#103283',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Fills the 16→20 gap so `h-18` (a w-12 poster at 2:3) exists —
            // Tailwind silently emits nothing for unknown classes.
            spacing: {
                18: '4.5rem',
            },
        },
    },

    plugins: [forms],
};

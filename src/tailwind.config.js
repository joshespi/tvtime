import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // App\Support\ButtonStyle holds the button palette as PHP constants.
        './app/Support/*.php',
    ],

    theme: {
        extend: {
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

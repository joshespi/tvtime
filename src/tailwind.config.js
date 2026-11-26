import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Shared with config/app.php (theme_colors), so the PWA chrome matches gray-50/900.
import paper from './resources/paper.json';

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
                gray: paper,
                indigo: {
                    50: '#fbefed', 100: '#f4deda', 200: '#ebc1bd',
                    300: '#efa9a2', 400: '#e98d83', 500: '#b44646',
                    600: '#92383c', 700: '#782d33', 800: '#60252c',
                    900: '#482028', 950: '#30161c',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Georgia', "'Times New Roman'", 'serif'],
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

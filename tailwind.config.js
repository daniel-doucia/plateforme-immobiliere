import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Vert profond : couleur principale
                brand: {
                    50: '#effaf1',
                    100: '#d8f3dc',
                    200: '#b7e4c7',
                    300: '#95d5b2',
                    400: '#74c69d',
                    500: '#40916c',
                    600: '#2d6a4f',
                    700: '#245a42',
                    800: '#1b4332',
                    900: '#14352a',
                    950: '#0b2218',
                },
                // Orange chaud : accent (ventes, appels à l'action secondaires)
                accent: {
                    50: '#fff5ec',
                    100: '#ffe6d0',
                    400: '#f4a261',
                    500: '#e9965c',
                    600: '#d97a3c',
                },
            },
        },
    },

    plugins: [forms],
};

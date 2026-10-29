import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

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
                // Orange de la maquette : accent
                accent: {
                    50: '#fff5ea',
                    100: '#fde4c8',
                    400: '#f59a3d',
                    500: '#e8730c',
                    600: '#c9610a',
                },
            },
        },
    },

    plugins: [forms],
};

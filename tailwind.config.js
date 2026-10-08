import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

/** Colours driven by CSS variables so users can switch accent and surface themes at runtime. */
const variablePalette = (name) =>
    Object.fromEntries(shades.map((shade) => [shade, `rgb(var(--${name}-${shade}) / <alpha-value>)`]));

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: variablePalette('primary'),
                gray: variablePalette('gray'),
            },
        },
    },

    plugins: [forms],
};

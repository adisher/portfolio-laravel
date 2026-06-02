import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Satoshi', ...defaultTheme.fontFamily.sans],
                satoshi: ['Satoshi', 'sans-serif'],
            },
            colors: {
                // Midnight Forest Palette
                midnight: {
                    DEFAULT: '#0D1B2A',
                    light: '#1B3A4B',
                    dark: '#06101A',
                },
                ocean: {
                    DEFAULT: '#1B3A4B',
                    light: '#2D5A6B',
                    dark: '#0F2A38',
                },
                teal: {
                    DEFAULT: '#41EAD4',
                    light: '#6EFCE5',
                    dark: '#2BC4B0',
                    50: '#EDFFFE',
                    100: '#D0FFFC',
                    200: '#A7FFF8',
                    300: '#6EFCE5',
                    400: '#41EAD4',
                    500: '#14D3BE',
                    600: '#0AAA9E',
                    700: '#0E877F',
                    800: '#116B66',
                    900: '#135854',
                },
                sunset: {
                    DEFAULT: '#FF6B35',
                    light: '#FF8A5C',
                    dark: '#E55A27',
                    50: '#FFF5F0',
                    100: '#FFE8DE',
                    200: '#FFCDB8',
                    300: '#FFAB87',
                    400: '#FF8A5C',
                    500: '#FF6B35',
                    600: '#E55A27',
                    700: '#C44A1D',
                    800: '#9E3D18',
                    900: '#7D3316',
                },
                soft: {
                    DEFAULT: '#E0E1DD',
                    light: '#F8F9FA',
                    dark: '#475B6B',
                },
                // Keep primary for backward compatibility
                primary: {
                    50: '#EDFFFE',
                    100: '#D0FFFC',
                    200: '#A7FFF8',
                    300: '#6EFCE5',
                    400: '#41EAD4',
                    500: '#14D3BE',
                    600: '#0AAA9E',
                    700: '#0E877F',
                    800: '#116B66',
                    900: '#135854',
                },
            },
            aspectRatio: {
                '4/3': '4 / 3',
                '3/2': '3 / 2',
                '2/3': '2 / 3',
                '9/16': '9 / 16',
            },
            boxShadow: {
                'glow': '0 0 30px rgba(65, 234, 212, 0.4)',
                'glow-sm': '0 0 15px rgba(65, 234, 212, 0.3)',
                'glow-sunset': '0 0 30px rgba(255, 107, 53, 0.4)',
                'glow-teal': '0 0 20px rgba(65, 234, 212, 0.25)',
            },
            animation: {
                'float': 'float 8s ease-in-out infinite',
                'float-delayed': 'float 8s ease-in-out infinite 4s',
                'fade-up': 'fadeUp 0.6s ease-out forwards',
                'fade-in': 'fadeIn 0.6s ease-out forwards',
                'score-pop': 'scorePop 0.5s ease-out',
                'wicket-flash': 'wicketShake 0.6s ease-out',
                'shine-sweep': 'shineSweep 0.6s ease-in-out',
                'gradient-shift': 'gradientShift 4s ease infinite',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0) rotate(0deg)' },
                    '50%': { transform: 'translateY(-20px) rotate(3deg)' },
                },
                fadeUp: {
                    '0%': { opacity: '0', transform: 'translateY(30px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                scorePop: {
                    '0%': { transform: 'scale(1)' },
                    '30%': { transform: 'scale(1.18)', color: '#41EAD4' },
                    '100%': { transform: 'scale(1)' },
                },
                wicketShake: {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '10%, 50%, 90%': { transform: 'translateX(-4px)' },
                    '30%, 70%': { transform: 'translateX(4px)' },
                },
                shineSweep: {
                    '0%': { transform: 'translateX(-100%) rotate(25deg)' },
                    '100%': { transform: 'translateX(200%) rotate(25deg)' },
                },
                gradientShift: {
                    '0%': { backgroundPosition: '0% 50%' },
                    '50%': { backgroundPosition: '100% 50%' },
                    '100%': { backgroundPosition: '0% 50%' },
                },
            },
            backgroundImage: {
                'gradient-radial': 'radial-gradient(circle, var(--tw-gradient-stops))',
            },
        },
    },

    plugins: [forms, typography],
    darkMode: 'class',
};

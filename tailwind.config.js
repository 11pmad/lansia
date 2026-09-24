import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#0F766E',
                    hover: '#115E59',
                    light: '#F0FDFA',
                    subtle: '#CCFBF1',
                },
                surface: {
                    DEFAULT: '#FFFFFF',
                    subtle: '#F8FAFC',
                    border: '#E2E8F0',
                },
                content: {
                    DEFAULT: '#0F172A',
                    secondary: '#475569',
                    muted: '#94A3B8',
                },
                success: {
                    DEFAULT: '#15803D',
                    bg: '#DCFCE7',
                },
                warning: {
                    DEFAULT: '#B45309',
                    bg: '#FEF3C7',
                },
                danger: {
                    DEFAULT: '#B91C1C',
                    bg: '#FEE2E2',
                },
                chart: {
                    total: '#0F766E',
                    in: '#0EA5E9',
                    out: '#F59E0B',
                    male: '#2563EB',
                    female: '#DB2777',
                    line: '#0F172A',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                card: '12px',
            },
            minHeight: {
                touch: '48px',
            },
            minWidth: {
                touch: '48px',
            },
        },
    },

    plugins: [forms],
};

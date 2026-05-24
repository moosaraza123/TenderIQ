/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.{js,jsx,ts,tsx,blade.php}',
        './app/Modules/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50:  '#f0fdfa',
                    100: '#ccfbf1',
                    200: '#99f6e4',
                    300: '#5eead4',
                    400: '#2dd4bf',
                    500: '#14b8a6',
                    600: '#0d9488',
                    700: '#0f766e',
                    800: '#115e59',
                    900: '#134e4a',
                },
                accent: {
                    50:  '#fffbeb',
                    100: '#fef3c7',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                },
                surface: {
                    50:  '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                },
                status: {
                    published:   '#10b981',
                    corrigendum: '#f59e0b',
                    cancelled:   '#ef4444',
                    closing:     '#ef4444',
                    apply:       '#10b981',
                    review:      '#f59e0b',
                    skip:        '#94a3b8',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                mono: ['JetBrains Mono', 'monospace'],
            },
            fontSize: {
                'tender-title': ['1.0625rem', { lineHeight: '1.5', fontWeight: '600' }],
            },
            boxShadow: {
                'card':       '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)',
                'card-hover': '0 4px 12px 0 rgb(0 0 0 / 0.08)',
                'card-focus': '0 0 0 3px rgb(20 184 166 / 0.15)',
            },
            borderRadius: {
                'card':   '12px',
                'badge':  '6px',
                'button': '8px',
            },
            keyframes: {
                fadeIn: {
                    '0%':   { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%':   { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%':      { opacity: '0.6' },
                },
                shimmer: {
                    '0%':   { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
            },
            animation: {
                'fade-in':    'fadeIn 200ms ease-out',
                'slide-up':   'slideUp 250ms ease-out',
                'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                'shimmer':    'shimmer 1.5s infinite linear',
            },
        },
    },
    plugins: [],
};

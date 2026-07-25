/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',
    content: [
        './**/*.php',
        '!./vendor/**',
        '!./node_modules/**',
        '!./database/**',
        '!./assets/**',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
                mono: ['JetBrains Mono', 'monospace'],
            },
            colors: {
                primary: { 50: '#ecfeff', 100: '#cffafe', 200: '#a5f3fc', 300: '#67e8f9', 400: '#22d3ee', 500: '#00d4ff', 600: '#0891b2', 700: '#0e7490', 800: '#155e75', 900: '#164e63' },
                accent: { 50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe', 300: '#c4b5fd', 400: '#a78bfa', 500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 800: '#5b21b6', 900: '#4c1d95' },
                surface: { 50: '#f8fafc', 100: '#f1f5f9', 800: '#1a1a2e', 900: '#12121a', 950: '#0a0a0f' },
            }
        }
    },
    safelist: [
        // Status badges - sparepart status
        'bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-900/30', 'dark:text-emerald-400',
        'bg-red-100', 'text-red-700', 'dark:bg-red-900/30', 'dark:text-red-400',
        'bg-amber-100', 'text-amber-700', 'dark:bg-amber-900/30', 'dark:text-amber-400',
        'bg-blue-100', 'text-blue-700', 'dark:bg-blue-900/30', 'dark:text-blue-400',
        'bg-purple-100', 'text-purple-700', 'dark:bg-purple-900/30', 'dark:text-purple-400',
        'bg-gray-100', 'text-gray-700', 'dark:bg-gray-700', 'dark:text-gray-300',
        'dark:bg-emerald-900/40', 'dark:bg-emerald-900/50',
        'dark:bg-red-900/40', 'dark:bg-red-900/50',
        'dark:bg-amber-900/40', 'dark:bg-amber-900/50',
        'dark:bg-blue-900/40', 'dark:bg-blue-900/50',
        // Transaction type classes in JS template literals
        'bg-emerald-100', 'text-emerald-600',
        'bg-red-100', 'text-red-600',
        'bg-amber-100', 'text-amber-600',
        'bg-blue-100', 'text-blue-600',
        'bg-purple-100', 'text-purple-600',
        // History page variants
        'text-emerald-800', 'text-emerald-700',
        'text-red-800', 'text-red-700',
        'text-amber-800', 'text-amber-700',
        'text-blue-800', 'text-blue-700',
        'text-purple-800', 'text-purple-700',
        // Gradient backgrounds
        'from-cyan-500', 'to-violet-500',
        'from-cyan-400', 'to-violet-400',
        'from-emerald-600', 'to-teal-500',
        'from-red-600', 'to-rose-500',
        // Ring variants for badges
        'ring-1', 'ring-emerald-600/10', 'ring-emerald-600/20', 'dark:ring-emerald-400/20', 'dark:ring-emerald-400/30',
        'ring-red-600/10', 'ring-red-600/20', 'dark:ring-red-400/20', 'dark:ring-red-400/30',
        'ring-amber-600/10', 'ring-amber-600/20', 'dark:ring-amber-400/20', 'dark:ring-amber-400/30',
        'ring-blue-600/10', 'ring-blue-600/20', 'dark:ring-blue-400/20', 'dark:ring-blue-400/30',
        'ring-gray-600/10', 'dark:ring-gray-400/20',
    ],
}

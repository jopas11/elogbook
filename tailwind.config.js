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
            },
            colors: {
                primary: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' },
                accent: { 50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4', 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e', 800: '#115e59', 900: '#134e4a' },
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
        'bg-gray-100', 'text-gray-700', 'dark:bg-gray-700', 'dark:text-gray-400',
        'dark:bg-emerald-900/40', 'dark:text-green-400',
        'dark:bg-red-900/40', 'dark:text-red-400',
        'dark:bg-amber-900/40', 'dark:text-amber-400',
        'dark:bg-blue-900/40', 'dark:text-blue-400',
        // Transaction type classes in JS template literals
        'bg-emerald-100', 'text-emerald-600', 'dark:bg-emerald-900/30', 'dark:text-emerald-400',
        'bg-red-100', 'text-red-600', 'dark:bg-red-900/30', 'dark:text-red-400',
        'bg-amber-100', 'text-amber-600', 'dark:bg-amber-900/30', 'dark:text-amber-400',
        'bg-blue-100', 'text-blue-600', 'dark:bg-blue-900/30', 'dark:text-blue-400',
        'bg-purple-100', 'text-purple-600', 'dark:bg-purple-900/30', 'dark:text-purple-400',
        'bg-gray-100', 'text-gray-600', 'dark:bg-gray-700', 'dark:text-gray-400',
        // History page variants
        'text-emerald-800', 'dark:bg-emerald-900/30', 'dark:text-emerald-400',
        'text-red-800', 'dark:bg-red-900/30', 'dark:text-red-400',
        'text-amber-800', 'dark:bg-amber-900/30', 'dark:text-amber-400',
        'text-blue-800', 'dark:bg-blue-900/30', 'dark:text-blue-400',
        'text-purple-800', 'dark:bg-purple-900/30', 'dark:text-purple-400',
        'text-gray-800', 'dark:bg-gray-700', 'dark:text-gray-400',
        // Edit form status options
        'bg-indigo-100', 'bg-indigo-200', 'dark:bg-indigo-900/30', 'dark:bg-indigo-900/40',
        'dark:bg-indigo-800/30',
        // Pagination active
        'bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-500/20',
        // Gradient backgrounds
        'from-indigo-50', 'to-purple-50', 'dark:from-gray-700/80', 'dark:to-gray-700/50',
        'from-emerald-600', 'to-teal-500',
        'from-red-600', 'to-rose-500',
        'from-indigo-600', 'to-purple-600',
    ],
}

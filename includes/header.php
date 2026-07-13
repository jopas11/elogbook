<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($page_title ?? APP_NAME) ?> — <?= APP_NAME ?></title>
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' }
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --color-primary: #1e40af;
            --color-primary-hover: #1e3a8a;
            --transition-base: 0.2s ease;
            --transition-modal: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Dark mode overrides via class */
        .dark {
            color-scheme: dark;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }

        /* Modal animations */
        .modal-enter {
            opacity: 0; transform: scale(0.95);
        }
        .modal-enter-active {
            opacity: 1; transform: scale(1);
            transition: opacity var(--transition-modal), transform var(--transition-modal);
        }
        .modal-leave {
            opacity: 1; transform: scale(1);
        }
        .modal-leave-active {
            opacity: 0; transform: scale(0.95);
            transition: opacity var(--transition-modal), transform var(--transition-modal);
        }

        /* Backdrop fade */
        .backdrop-enter { opacity: 0; }
        .backdrop-enter-active { opacity: 1; transition: opacity 0.2s ease; }
        .backdrop-leave { opacity: 1; }
        .backdrop-leave-active { opacity: 0; transition: opacity 0.2s ease; }

        /* Toast slide-in */
        @keyframes toast-in {
            from { opacity: 0; transform: translateX(100%) scale(0.95); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes toast-out {
            from { opacity: 1; transform: translateX(0) scale(1); }
            to { opacity: 0; transform: translateX(100%) scale(0.95); }
        }
        .toast-enter { animation: toast-in 0.35s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        .toast-leave { animation: toast-out 0.3s ease forwards; }

        /* Page fade-in */
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-enter { animation: fade-in 0.3s ease-out; }

        /* Skeleton / loading pulse */
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .animate-pulse-slow { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

        /* Loading spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { display: inline-block; width: 1.25rem; height: 1.25rem; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; }

        /* Sidebar slide (mobile) */
        @keyframes slide-in-left {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        @keyframes slide-out-left {
            from { transform: translateX(0); }
            to { transform: translateX(-100%); }
        }
        .sidebar-enter { animation: slide-in-left 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        .sidebar-leave { animation: slide-out-left 0.25s ease forwards; }

        /* Alpine x-cloak */
        [x-cloak] { display: none !important; }

        /* Utility: truncate 2 lines */
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
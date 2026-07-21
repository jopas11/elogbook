<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape(isset($page_title) ? $page_title : APP_NAME) ?> — <?= APP_NAME ?></title>
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
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
            }
        }
    </script>
    <style>
        :root {
            --color-primary: #4f46e5;
            --color-primary-hover: #4338ca;
            --color-accent: #14b8a6;
            --transition-base: 0.2s ease;
            --transition-modal: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html { scroll-behavior: smooth; }

        .dark { color-scheme: dark; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }

        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity var(--transition-modal), transform var(--transition-modal); }
        .modal-leave { opacity: 1; transform: scale(1); }
        .modal-leave-active { opacity: 0; transform: scale(0.95); transition: opacity var(--transition-modal), transform var(--transition-modal); }

        .backdrop-enter { opacity: 0; }
        .backdrop-enter-active { opacity: 1; transition: opacity 0.2s ease; }
        .backdrop-leave { opacity: 1; }
        .backdrop-leave-active { opacity: 0; transition: opacity 0.2s ease; }

        @keyframes toast-in { from { opacity: 0; transform: translateX(100%) scale(0.95); } to { opacity: 1; transform: translateX(0) scale(1); } }
        @keyframes toast-out { from { opacity: 1; transform: translateX(0) scale(1); } to { opacity: 0; transform: translateX(100%) scale(0.95); } }
        .toast-enter { animation: toast-in 0.35s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        .toast-leave { animation: toast-out 0.3s ease forwards; }

        @keyframes page-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .page-enter { animation: page-in 0.35s ease-out; }

        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .animate-pulse-slow { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { display: inline-block; width: 1.25rem; height: 1.25rem; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; }

        @keyframes slide-in-left { from { transform: translateX(-100%); } to { transform: translateX(0); } }
        @keyframes slide-out-left { from { transform: translateX(0); } to { transform: translateX(-100%); } }
        .sidebar-enter { animation: slide-in-left 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        .sidebar-leave { animation: slide-out-left 0.25s ease forwards; }

        [x-cloak] { display: none !important; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 25px -5px rgba(0,0,0,0.1); }
        .dark .card-hover:hover { box-shadow: 0 8px 25px -5px rgba(0,0,0,0.3); }

        @keyframes card-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .animate-card-in { animation: card-in 0.3s ease-out both; }

        .responsive-table { border-collapse: collapse; width: 100%; }
        @media (max-width: 767px) {
            .responsive-table thead { display: none; }
            .responsive-table tbody, .responsive-table tr, .responsive-table td { display: block; }
            .responsive-table tr { padding: 12px; background: inherit !important; border-bottom: 1px solid #e5e7eb; }
            .dark .responsive-table tr { border-bottom-color: #374151; }
            .responsive-table td { display: flex; justify-content: space-between; align-items: center; padding: 6px 12px !important; text-align: right !important; border: none !important; }
            .responsive-table td[data-label]::before { content: attr(data-label); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
            .dark .responsive-table td[data-label]::before { color: #9ca3af; }
            .responsive-table td.hidden { display: none !important; }
            .responsive-table td.no-label { display: block !important; width: 100% !important; padding: 0 !important; text-align: left !important; }
            .responsive-table td.no-label::before { display: none !important; }
        }

        /* Dashboard sortable table — mobile card view */
        @media (max-width: 639px) {
            .dashboard-sortable-table thead { display: none; }
            .dashboard-sortable-table tbody { display: flex; flex-direction: column; gap: 12px; padding: 12px; }
            .dashboard-sortable-table tr {
                display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
                background: white !important; border-radius: 12px; padding: 14px;
                border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
                transition: all 0.2s ease;
            }
            .dark .dashboard-sortable-table tr {
                background: #1f2937 !important; border-color: #374151;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
            .dashboard-sortable-table tr:hover {
                transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            }
            .dark .dashboard-sortable-table tr:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }
            .dashboard-sortable-table td {
                display: flex; align-items: center; gap: 6px; padding: 4px 0 !important;
                text-align: left !important; border: none !important; background: transparent !important;
            }
            .dashboard-sortable-table td[data-label]::before {
                content: attr(data-label); font-weight: 700; font-size: 10px; text-transform: uppercase;
                letter-spacing: 0.05em; color: #9ca3af; min-width: 60px; flex-shrink: 0;
            }
            .dark .dashboard-sortable-table td[data-label]::before { color: #6b7280; }
            .dashboard-sortable-table td.hidden { display: none !important; }
            .dashboard-sortable-table td.no-label { display: flex !important; padding: 0 !important; text-align: left !important; }
            .dashboard-sortable-table td.no-label::before { display: none !important; }
            /* Row 1: thumbnail + kategori + jenis */
            .dashboard-sortable-table tr td:first-child {
                width: 100%; display: flex; align-items: center; gap: 10px; padding-bottom: 6px !important;
                border-bottom: 1px solid #f3f4f6 !important;
            }
            .dark .dashboard-sortable-table tr td:first-child { border-bottom-color: #374151 !important; }
            /* Status badges row — full width */
            .dashboard-sortable-table tr td:nth-child(7),
            .dashboard-sortable-table tr td:nth-child(8),
            .dashboard-sortable-table tr td:nth-child(9),
            .dashboard-sortable-table tr td:nth-child(10) {
                flex: 0 0 auto; padding: 2px 0 !important;
            }
            .dashboard-sortable-table tr td:nth-child(7)::before,
            .dashboard-sortable-table tr td:nth-child(8)::before,
            .dashboard-sortable-table tr td:nth-child(9)::before,
            .dashboard-sortable-table tr td:nth-child(10)::before { display: none !important; }
            /* Aksi — full width */
            .dashboard-sortable-table tr td:last-child {
                width: 100%; justify-content: flex-end; padding-top: 8px !important;
                border-top: 1px solid #f3f4f6 !important;
            }
            .dark .dashboard-sortable-table tr td:last-child { border-top-color: #374151 !important; }
        }

        /* SweetAlert detail modals */
        .detail-group-modal { border-radius: 1.25rem !important; max-width: 920px !important; }
        .detail-item-modal { border-radius: 1.25rem !important; max-width: 620px !important; }
        .history-modal { border-radius: 1.25rem !important; max-width: 570px !important; }
        .swal2-container .detail-item-modal .swal2-html-container,
        .swal2-container .detail-group-modal .swal2-html-container,
        .swal2-container .history-modal .swal2-html-container { margin: 0 1.25rem !important; max-height: 80vh !important; overflow-y: auto; }
        .swal2-popup.swal2-modal { padding-top: 1.25rem !important; }

        /* Image zoom lightbox */
        @keyframes fadeInZoom { from { opacity: 0; } to { opacity: 1; } }
        @keyframes imgZoomIn { from { opacity: 0; transform: scale(0.85); } to { opacity: 1; transform: scale(1); } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
    function toggleImageZoom(src, alt) {
        var existing = document.getElementById('img-zoom-overlay');
        if (existing) { existing.remove(); return; }
        var overlay = document.createElement('div');
        overlay.id = 'img-zoom-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.75);backdrop-filter:blur(8px);cursor:zoom-out;animation:fadeInZoom 0.2s ease';
        overlay.onclick = function() { overlay.remove(); };
        var img = document.createElement('img');
        img.src = src;
        img.alt = alt || '';
        img.style.cssText = 'max-width:90vw;max-height:90vh;object-fit:contain;border-radius:12px;box-shadow:0 25px 60px rgba(0,0,0,0.5);animation:imgZoomIn 0.25s ease';
        overlay.appendChild(img);
        document.body.appendChild(overlay);
        document.addEventListener('keydown', function handler(e) {
            if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', handler); }
        });
    }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-200 font-sans antialiased">
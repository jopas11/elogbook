<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape(isset($page_title) ? $page_title : APP_NAME) ?> — <?= APP_NAME ?></title>
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        primary: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
                        accent: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
                        surface: { 50: '#f8fafc', 100: '#f1f5f9', 800: '#1e293b', 900: '#0f172a', 950: '#020617' },
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --color-primary: #2563eb;
            --color-primary-hover: #1d4ed8;
            --color-accent: #2563eb;
            --color-accent-hover: #1d4ed8;
            --transition-base: 0.2s ease;
            --transition-modal: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            --glow-primary: none;
            --glow-accent: none;
        }

        html { scroll-behavior: smooth; font-size: 15px; }
        .dark { color-scheme: dark; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #2a2a3e; }
        .dark ::-webkit-scrollbar-thumb:hover { background: #3a3a5e; }

        /* ===== AURORA VOID DESIGN SYSTEM ===== */

        /* Glass panels */
        .glass-panel {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.5);
        }
        .dark .glass-panel {
            background: rgba(26, 26, 46, 0.7);
            border-color: rgba(255,255,255,0.06);
        }
        .glass-panel-strong {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.6);
        }
        .dark .glass-panel-strong {
            background: rgba(26, 26, 46, 0.85);
            border-color: rgba(255,255,255,0.08);
        }

        /* Glow on hover */
        .glow-on-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .glow-on-hover:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border-color: rgba(37, 99, 235, 0.2);
            transform: translateY(-2px);
        }
        .dark .glow-on-hover:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border-color: rgba(37, 99, 235, 0.15);
        }

        /* Gradient text */
        .gradient-text {
            background: none;
            -webkit-text-fill-color: #1e293b;
            color: #1e293b;
        }
        .dark .gradient-text {
            background: none;
            -webkit-text-fill-color: #f1f5f9;
            color: #f1f5f9;
        }

        /* Aurora background */
        .aurora-bg {
            background: #f8fafc;
        }
        .dark .aurora-bg {
            background: #0f172a;
        }

        /* Magnetic button */
        .magnetic-btn {
            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        }
        .magnetic-btn:hover {
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
        }

        /* Shimmer loading */
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .shimmer {
            background: linear-gradient(90deg, transparent 25%, rgba(37,99,235,0.06) 50%, transparent 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s ease-in-out infinite;
        }

        /* Aurora orbs animation */
        @keyframes aurora-float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -20px) scale(1.05); }
            50% { transform: translate(-20px, 20px) scale(0.95); }
            75% { transform: translate(15px, 10px) scale(1.02); }
        }
        @keyframes aurora-float-2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-25px, 15px) scale(1.03); }
            66% { transform: translate(20px, -25px) scale(0.97); }
        }
        .aurora-orb { animation: aurora-float 12s ease-in-out infinite; }
        .aurora-orb-2 { animation: aurora-float-2 15s ease-in-out infinite; }
        .aurora-orb-3 { animation: aurora-float 18s ease-in-out infinite reverse; }

        /* Particle */
        .aurora-particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
            animation: particle-drift linear infinite;
        }
        @keyframes particle-drift {
            0% { opacity: 0; transform: translateY(0) scale(0); }
            10% { opacity: 1; transform: scale(1); }
            90% { opacity: 1; }
            100% { opacity: 0; transform: translateY(-100vh) scale(0.5); }
        }

        /* Cursor glow */
        #cursor-glow {
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 99998;
            opacity: 0;
            transition: opacity 0.3s ease;
            background: radial-gradient(circle, rgba(37,99,235,0.05) 0%, transparent 70%);
            transform: translate(-50%, -50%);
        }
        .dark #cursor-glow {
            background: radial-gradient(circle, rgba(37,99,235,0.08) 0%, transparent 70%);
        }
        body:hover #cursor-glow { opacity: 1; }
        @media (pointer: coarse) { #cursor-glow { display: none !important; } }

        /* Page transitions */
        @keyframes page-in {
            from { opacity: 0; transform: translateY(12px); filter: blur(4px); }
            to { opacity: 1; transform: translateY(0); filter: blur(0); }
        }
        .page-enter { animation: page-in 0.4s cubic-bezier(0.22, 1, 0.36, 1); }

        @keyframes page-out {
            from { opacity: 1; transform: translateY(0); filter: blur(0); }
            to { opacity: 0; transform: translateY(-8px); filter: blur(4px); }
        }
        .page-exit { animation: page-out 0.2s ease forwards; }

        /* Modal */
        .modal-enter { opacity: 0; transform: scale(0.92) translateY(10px); }
        .modal-enter-active { opacity: 1; transform: scale(1) translateY(0); transition: opacity var(--transition-modal), transform var(--transition-modal); }
        .modal-leave { opacity: 1; transform: scale(1) translateY(0); }
        .modal-leave-active { opacity: 0; transform: scale(0.92) translateY(10px); transition: opacity 0.2s ease, transform 0.2s ease; }

        .backdrop-enter { opacity: 0; }
        .backdrop-enter-active { opacity: 1; transition: opacity 0.3s ease; }
        .backdrop-leave { opacity: 1; }
        .backdrop-leave-active { opacity: 0; transition: opacity 0.2s ease; }

        /* Toast */
        @keyframes toast-in { from { opacity: 0; transform: translateX(100%) scale(0.9); filter: blur(4px); } to { opacity: 1; transform: translateX(0) scale(1); filter: blur(0); } }
        @keyframes toast-out { from { opacity: 1; transform: translateX(0) scale(1); } to { opacity: 0; transform: translateX(100%) scale(0.9); } }
        .toast-enter { animation: toast-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
        .toast-leave { animation: toast-out 0.3s ease forwards; }

        /* Spinner */
        @keyframes aurora-spin { to { transform: rotate(360deg); } }
        .spinner {
            display: inline-block; width: 1.25rem; height: 1.25rem;
            border: 2px solid currentColor; border-right-color: transparent;
            border-radius: 50%; animation: aurora-spin 0.7s linear infinite;
        }

        /* Sidebar */
        @keyframes slide-in-left { from { transform: translateX(-100%); } to { transform: translateX(0); } }
        @keyframes slide-out-left { from { transform: translateX(0); } to { transform: translateX(-100%); } }
        .sidebar-enter { animation: slide-in-left 0.3s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
        .sidebar-leave { animation: slide-out-left 0.25s ease forwards; }

        [x-cloak] { display: none !important; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* Card hover */
        .card-hover { transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease, border-color 0.3s ease; }
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border-color: rgba(37, 99, 235, 0.15);
        }
        .dark .card-hover:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border-color: rgba(37, 99, 235, 0.1);
        }

        @keyframes card-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-card-in { animation: card-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) both; }

        /* Animated counter */
        .counter-value { font-variant-numeric: tabular-nums; }

        /* Pulse glow */
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.1); }
            50% { box-shadow: 0 2px 12px 2px rgba(37, 99, 235, 0.08); }
        }
        .pulse-glow { animation: pulse-glow 3s ease-in-out infinite; }

        /* Responsive table */
        .responsive-table { border-collapse: collapse; width: 100%; }
        @media (max-width: 767px) {
            .responsive-table thead { display: none; }
            .responsive-table tbody, .responsive-table tr, .responsive-table td { display: block; }
            .responsive-table tr {
                padding: 14px; background: inherit !important;
                border-bottom: 1px solid rgba(0,0,0,0.06);
            }
            .dark .responsive-table tr { border-bottom-color: rgba(255,255,255,0.06); }
            .responsive-table td {
                display: flex; justify-content: space-between; align-items: center;
                padding: 6px 14px !important; text-align: right !important; border: none !important;
            }
            .responsive-table td[data-label]::before {
                content: attr(data-label); font-weight: 600; font-size: 11px;
                text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8;
            }
            .responsive-table td.hidden { display: none !important; }
            .responsive-table td.no-label { display: block !important; width: 100% !important; padding: 0 !important; text-align: left !important; }
            .responsive-table td.no-label::before { display: none !important; }
        }

        /* Dashboard sortable table — mobile card view */
        @media (max-width: 639px) {
            .dashboard-sortable-table thead { display: none; }
            .dashboard-sortable-table tbody { display: flex; flex-direction: column; gap: 10px; padding: 10px; }
            .dashboard-sortable-table tr {
                display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
                border-radius: 14px; padding: 14px;
                border: 1px solid rgba(0,212,255,0.08);
                transition: all 0.3s ease;
            }
            .dashboard-sortable-table tr {
                background: rgba(255,255,255,0.8) !important;
                backdrop-filter: blur(10px);
            }
            .dark .dashboard-sortable-table tr {
                background: rgba(26, 26, 46, 0.6) !important;
                border-color: rgba(255,255,255,0.06);
            }
            .dashboard-sortable-table tr:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 20px rgba(0,212,255,0.08);
            }
            .dashboard-sortable-table td {
                display: flex; align-items: center; gap: 6px; padding: 5px 0 !important;
                text-align: left !important; border: none !important; background: transparent !important;
            }
            .dashboard-sortable-table td[data-label]::before {
                content: attr(data-label); font-weight: 700; font-size: 10px; text-transform: uppercase;
                letter-spacing: 0.06em; color: #64748b; min-width: 60px; flex-shrink: 0;
            }
            .dark .dashboard-sortable-table td[data-label]::before { color: #475569; }
            .dashboard-sortable-table td.hidden { display: none !important; }
            .dashboard-sortable-table td.no-label { display: flex !important; padding: 0 !important; text-align: left !important; }
            .dashboard-sortable-table td.no-label::before { display: none !important; }
            .dashboard-sortable-table tr td:first-child {
                width: 100%; display: flex; align-items: center; gap: 10px; padding-bottom: 6px !important;
                border-bottom: 1px solid rgba(0,0,0,0.04) !important;
            }
            .dark .dashboard-sortable-table tr td:first-child { border-bottom-color: rgba(255,255,255,0.06) !important; }
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
            .dashboard-sortable-table tr td:last-child {
                width: 100%; justify-content: flex-end; padding-top: 8px !important;
                border-top: 1px solid rgba(0,0,0,0.04) !important;
            }
            .dark .dashboard-sortable-table tr td:last-child { border-top-color: rgba(255,255,255,0.06) !important; }
        }

        /* SweetAlert detail modals */
        .detail-group-modal { border-radius: 1.25rem !important; max-width: 920px !important; }
        .detail-item-modal { border-radius: 1.25rem !important; max-width: 620px !important; }
        .history-modal { border-radius: 1.25rem !important; max-width: 570px !important; width: 92vw !important; }
        .swal2-container .detail-item-modal .swal2-html-container,
        .swal2-container .detail-group-modal .swal2-html-container,
        .swal2-container .history-modal .swal2-html-container { margin: 0 1.25rem !important; max-height: 80vh !important; overflow-y: auto; font-size: 15px !important; }
        .swal2-popup.swal2-modal { padding-top: 1.25rem !important; }
        .swal2-title { font-size: 1.25rem !important; }
        @media (max-width: 640px) {
            .history-modal { border-radius: 1rem !important; margin: 0.25rem !important; width: calc(100vw - 0.5rem) !important; }
            .swal2-container .history-modal .swal2-html-container { margin: 0 0.75rem !important; padding: 0 !important; font-size: 14px !important; }
            .swal2-container .history-modal .swal2-title { font-size: 1rem !important; padding: 0.75rem 0.75rem 0.5rem !important; }
            .swal2-container .history-modal .swal2-actions { padding: 0.5rem !important; }
        }

        /* Image zoom lightbox */
        @keyframes fadeInZoom { from { opacity: 0; } to { opacity: 1; } }
        @keyframes imgZoomIn { from { opacity: 0; transform: scale(0.85); } to { opacity: 1; transform: scale(1); } }

        /* Status badge glow */
        .badge-glow { position: relative; }
        .badge-glow::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0.4;
            filter: blur(6px);
            z-index: -1;
        }
        .badge-glow-emerald::after { background: #10b981; opacity: 0.25; }
        .badge-glow-red::after { background: #ef4444; opacity: 0.25; }
        .badge-glow-amber::after { background: #f59e0b; opacity: 0.25; }
        .badge-glow-blue::after { background: #3b82f6; opacity: 0.25; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
    /* Cursor glow */
    (function() {
        var glow = document.createElement('div');
        glow.id = 'cursor-glow';
        document.body.appendChild(glow);
        var mx = 0, my = 0, gx = 0, gy = 0;
        document.addEventListener('mousemove', function(e) { mx = e.clientX; my = e.clientY; });
        function animate() {
            gx += (mx - gx) * 0.08;
            gy += (my - gy) * 0.08;
            glow.style.left = gx + 'px';
            glow.style.top = gy + 'px';
            requestAnimationFrame(animate);
        }
        animate();
    })();

    /* Particle system */
    function createParticles(container, count) {
        if (!container) return;
        var colors = ['rgba(37,99,235,0.3)', 'rgba(59,130,246,0.25)', 'rgba(16,185,129,0.2)', 'rgba(37,99,235,0.15)'];
        for (var i = 0; i < count; i++) {
            var p = document.createElement('div');
            p.className = 'aurora-particle';
            var size = Math.random() * 4 + 2;
            p.style.cssText = 'width:' + size + 'px;height:' + size + 'px;left:' + (Math.random()*100) + '%;bottom:' + (-10) + '%;background:' + colors[Math.floor(Math.random()*colors.length)] + ';animation-duration:' + (Math.random()*10+8) + 's;animation-delay:' + (Math.random()*8) + 's;';
            container.appendChild(p);
        }
    }

    /* Animated counter */
    function animateCounter(el, target, duration) {
        if (!el || el.dataset.animated === 'true') return;
        el.dataset.animated = 'true';
        duration = duration || 1200;
        var start = 0;
        var startTime = null;
        function step(ts) {
            if (!startTime) startTime = ts;
            var progress = Math.min((ts - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(eased * target);
            el.textContent = current.toLocaleString('id-ID');
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('id-ID');
        }
        requestAnimationFrame(step);
    }

    /* Magnetic button effect */
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.magnetic-btn').forEach(function(btn) {
            btn.addEventListener('mousemove', function(e) {
                var rect = btn.getBoundingClientRect();
                var x = e.clientX - rect.left - rect.width / 2;
                var y = e.clientY - rect.top - rect.height / 2;
                btn.style.transform = 'translate(' + (x * 0.15) + 'px, ' + (y * 0.15) + 'px) scale(1.02)';
            });
            btn.addEventListener('mouseleave', function() {
                btn.style.transform = '';
            });
        });
    });

    /* Page transition */
    function pageTransition(url) {
        document.body.classList.add('page-exit');
        setTimeout(function() { window.location.href = url; }, 200);
    }

    /* Image zoom */
    function toggleImageZoom(src, alt) {
        var existing = document.getElementById('img-zoom-overlay');
        if (existing) { existing.remove(); return; }
        var overlay = document.createElement('div');
        overlay.id = 'img-zoom-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(10,10,15,0.85);backdrop-filter:blur(12px);cursor:zoom-out;animation:fadeInZoom 0.25s ease';
        overlay.onclick = function() { overlay.remove(); };
        var img = document.createElement('img');
        img.src = src;
        img.alt = alt || '';
        img.style.cssText = 'max-width:90vw;max-height:90vh;object-fit:contain;border-radius:16px;box-shadow:0 0 60px rgba(0,212,255,0.15),0 25px 60px rgba(0,0,0,0.5);animation:imgZoomIn 0.3s cubic-bezier(0.22,1,0.36,1);border:1px solid rgba(255,255,255,0.1)';
        overlay.appendChild(img);
        document.body.appendChild(overlay);
        document.addEventListener('keydown', function handler(e) {
            if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', handler); }
        });
    }

    /* Parse multi-image JSON array or single path string */
    function imgList(val) {
        if (!val) return [];
        if (Array.isArray(val)) return val;
        if (typeof val === 'string') {
            var t = val.trim();
            if (t.charAt(0) === '[') {
                try {
                    var arr = JSON.parse(t);
                    return Array.isArray(arr) ? arr.filter(Boolean) : [t];
                } catch (e) { return [t]; }
            }
            return [t];
        }
        return [];
    }
    </script>
</head>
<body class="aurora-bg text-gray-900 dark:text-gray-100 transition-colors duration-300 font-sans antialiased">

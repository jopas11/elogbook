<?php $currentPage = _get($_GET, 'url', str_replace('.php', '', basename($_SERVER['PHP_SELF']))); ?>
<script>
    if (localStorage.getItem('sidebarVisible') === 'false' && window.innerWidth >= 1024) {
        document.write('<style id="pre-collapse">');
        document.write('#sidebar{width:5rem!important}');
        document.write('.content-wrapper{margin-left:5rem!important;padding-top:1rem!important}');
            document.write('.sidebar-name{display:none!important}');
            document.write('#sidebar nav a span{display:none!important}');
            document.write('#sidebar nav a{justify-content:center;padding:.5rem!important}');
            document.write('#sidebar .menu-label,#sidebar .admin-label,#sidebar hr{display:none!important}');
            document.write('#sidebar .bottom-section .bottom-label{display:none!important}');
            document.write('#sidebar .bottom-section .bottom-btn{justify-content:center;padding:.5rem!important}');
            document.write('#sidebar .bottom-section .bottom-btn span{display:none!important}');
            document.write('#sidebar .bottom-section .bottom-user{justify-content:center;padding:.5rem!important}');
        document.write('#sidebar .sidebar-icon-wrap{width:2rem;height:2rem}');
        document.write('#sidebar .sidebar-icon-wrap i{font-size:.75rem}');
        document.write('#sidebar .sidebar-toggle{padding:.25rem}');
        document.write('</style>');
    }
</script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sidebar', () => ({
                mobileOpen: false,
                sidebarVisible: localStorage.getItem('sidebarVisible') !== 'false',
                isDark: localStorage.getItem('darkMode') === 'true',
                showLogoutModal: false,
                init() {
                    if (this.isDark) document.documentElement.classList.add('dark');
                    this.$nextTick(() => {
                        var el = document.getElementById('pre-collapse');
                        if (el) el.remove();
                    });
                },
                isDesktop() {
                    return window.innerWidth >= 1024;
                },
                toggleSidebar() {
                    if (this.isDesktop()) {
                        this.sidebarVisible = !this.sidebarVisible;
                        localStorage.setItem('sidebarVisible', this.sidebarVisible);
                    } else {
                        this.mobileOpen = !this.mobileOpen;
                    }
                },
                closeNav() {
                    this.mobileOpen = false;
                },
                toggleDark() {
                    this.isDark = !this.isDark;
                    document.documentElement.classList.toggle('dark', this.isDark);
                    localStorage.setItem('darkMode', this.isDark);
                },
                confirmLogout() {
                    window.location.href = '<?= pageUrl('logout.php') ?>';
                }
            }));
        });
    </script>
    <style>
        #sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 16rem;
            background: linear-gradient(to bottom, #3730a3, #312e81, #4c1d95);
            color: white;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            z-index: 30;
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out, width 0.3s ease-in-out;
        }
        .dark #sidebar {
            background: linear-gradient(to bottom, #111827, #030712, #030712);
        }
        #sidebar.open {
            transform: translateX(0);
        }
        .sidebar-body {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
        }
        @media (min-width: 1024px) {
            #sidebar {
                transform: translateX(0);
                transition: width 0.3s ease-in-out;
            }
            .content-wrapper {
                margin-left: 16rem;
                transition: margin-left 0.3s ease-in-out;
            }
            .sidebar-collapsed #sidebar {
                width: 5rem;
            }
            .sidebar-collapsed .content-wrapper {
                margin-left: 5rem;
                padding-top: 1rem;
            }
            .sidebar-collapsed #sidebar .px-4 {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
                gap: 0.25rem;
                justify-content: center;
            }
            .sidebar-collapsed #sidebar .sidebar-icon-wrap {
                width: 2rem;
                height: 2rem;
            }
            .sidebar-collapsed #sidebar .sidebar-icon-wrap i {
                font-size: 0.75rem;
            }
            .sidebar-collapsed #sidebar .sidebar-toggle {
                padding: 0.25rem;
            }
            .sidebar-collapsed #sidebar .sidebar-name {
                display: none;
            }
            .sidebar-collapsed #sidebar nav a span {
                display: none;
            }
            .sidebar-collapsed #sidebar nav a {
                justify-content: center;
                padding: 0.5rem !important;
            }
            .sidebar-collapsed #sidebar .menu-label,
            .sidebar-collapsed #sidebar .admin-label,
            .sidebar-collapsed #sidebar hr {
                display: none;
            }
            .sidebar-collapsed #sidebar .bottom-section .bottom-label {
                display: none;
            }
            .sidebar-collapsed #sidebar .bottom-section .bottom-btn {
                justify-content: center;
                padding: 0.5rem !important;
            }
            .sidebar-collapsed #sidebar .bottom-section .bottom-btn span {
                display: none;
            }
            .sidebar-collapsed #sidebar .bottom-section .bottom-user {
                justify-content: center;
                padding: 0.5rem;
            }
        }
    </style>

<div x-data="sidebar()" :class="{'sidebar-collapsed': isDesktop() && !sidebarVisible}" class="relative min-h-screen">
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm lg:hidden" @click="closeNav()" x-transition.opacity></div>

    <div id="sidebar" :class="{'open': mobileOpen}">
    <div class="flex items-center gap-3 px-4 h-16 border-b border-white/10 shrink-0">
        <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shrink-0 shadow-inner sidebar-icon-wrap">
            <i class="fa-solid fa-boxes-stacked text-white text-sm"></i>
        </div>
        <div class="flex-1 min-w-0 sidebar-name">
            <h1 class="font-bold text-base truncate tracking-tight"><?= APP_NAME ?></h1>
        </div>
        <button @click="toggleSidebar()" class="flex p-1.5 rounded-lg text-white/60 hover:bg-white/10 hover:text-white transition sidebar-toggle" title="Toggle sidebar">
            <i :class="isDesktop() ? (sidebarVisible ? 'fa-solid fa-chevron-left' : 'fa-solid fa-chevron-right') : 'fa-solid fa-xmark'" class="text-sm"></i>
        </button>
    </div>

    <div class="sidebar-body">
    <?php
    $navItems = [
        ['url' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
        ['url' => 'sparepart_keluar.php', 'label' => 'Sparepart Keluar', 'icon' => 'fa-right-from-bracket'],
        ['url' => 'history.php', 'label' => 'History', 'icon' => 'fa-clock-rotate-left'],
    ];
    $adminItems = [
        ['url' => 'jenis_sparepart.php', 'label' => 'Jenis & Type', 'icon' => 'fa-tags'],
        ['url' => 'ubah_status.php', 'label' => 'Ubah Status', 'icon' => 'fa-arrows-rotate'],
        ['url' => 'users.php', 'label' => 'Kelola User', 'icon' => 'fa-users-gear'],
    ];
    ?>
    <div class="px-3 pt-3 pb-1">
        <p class="text-[11px] font-semibold text-white/40 uppercase tracking-widest px-3 menu-label">Menu</p>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 pb-3 space-y-0.5">
        <?php foreach ($navItems as $item):
            $active = $currentPage === str_replace('.php', '', $item['url']);
        ?>
        <a href="<?= pageUrl($item['url']) ?>" @click="closeNav()"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?= $active ? 'bg-white/20 text-white shadow-lg shadow-indigo-500/20' : 'text-white/70 hover:bg-white/10 hover:text-white hover:translate-x-0.5' ?>">
            <i class="fa-solid <?= $item['icon'] ?> w-5 text-center text-sm"></i>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
        <?php if (isAdmin()): ?>
        <hr class="border-white/10 my-2">
        <p class="text-[11px] font-semibold text-white/40 uppercase tracking-widest px-3 mb-1 admin-label">Admin</p>
        <?php foreach ($adminItems as $item):
            $active = $currentPage === str_replace('.php', '', $item['url']);
        ?>
        <a href="<?= pageUrl($item['url']) ?>" @click="closeNav()"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?= $active ? 'bg-white/20 text-white shadow-lg shadow-indigo-500/20' : 'text-white/70 hover:bg-white/10 hover:text-white hover:translate-x-0.5' ?>">
            <i class="fa-solid <?= $item['icon'] ?> w-5 text-center text-sm"></i>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <div class="border-t border-white/10 p-3 space-y-2 shrink-0 bottom-section">
        <button @click="toggleDark()"
                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white transition-all duration-200 bottom-btn">
            <i :class="isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon'" class="w-5 text-center text-sm"></i>
            <span class="bottom-label" x-text="isDark ? 'Mode Terang' : 'Mode Gelap'"></span>
        </button>

        <div class="flex items-center gap-3 px-3 py-2 bottom-section bottom-user">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-lg shadow-indigo-500/30">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div class="bottom-label min-w-0 flex-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span class="text-sm font-semibold truncate"><?= escape($user['name']) ?></span>
                <span class="shrink-0"><?= getRoleBadge($user['role']) ?></span>
            </div>
        </div>
        <div class="flex gap-1 bottom-section">
            <a href="<?= pageUrl('profile.php') ?>" @click="closeNav()"
               class="bottom-btn flex-1 flex items-center justify-center gap-1.5 text-xs px-2 py-1.5 rounded-xl text-white/60 hover:bg-white/10 hover:text-white transition-all duration-200">
                <i class="fa-solid fa-user"></i> <span class="bottom-label">Profil</span>
            </a>
            <a href="#" @click.prevent="showLogoutModal = true"
               class="bottom-btn flex-1 flex items-center justify-center gap-1.5 text-xs px-2 py-1.5 rounded-xl text-red-300/70 hover:bg-red-500/20 hover:text-red-200 transition-all duration-200">
                <i class="fa-solid fa-right-from-bracket"></i> <span class="bottom-label">Logout</span>
            </a>
        </div>
    </div>
</div>
</div>

    <div class="content-wrapper min-h-screen pt-4 lg:pt-6 p-4 lg:p-6 page-enter">
    <!-- Top bar: shown when sidebar is closed (mobile or desktop collapsed) -->
    <div x-show="isDesktop() ? !sidebarVisible : !mobileOpen" x-cloak
         class="flex items-center gap-3 px-4 h-14 mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <button @click="toggleSidebar()" class="lg:hidden p-2 -ml-1 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-md shrink-0">
            <i class="fa-solid fa-boxes-stacked text-white text-sm"></i>
        </div>
        <h1 class="font-bold text-base text-gray-800 dark:text-white"><?= APP_NAME ?></h1>
    </div>
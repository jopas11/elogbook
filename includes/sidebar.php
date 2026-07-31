<?php
require_once __DIR__ . '/../helpers/functions.php';
$currentPage = _get($_GET, 'route', _get($_GET, 'url', str_replace('.php', '', basename($_SERVER['PHP_SELF']))));
?>
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
                windowWidth: window.innerWidth,
                init() {
                    if (this.isDark) document.documentElement.classList.add('dark');
                    this.$nextTick(() => {
                        var el = document.getElementById('pre-collapse');
                        if (el) el.remove();
                    });
                    window.addEventListener('resize', () => {
                        this.windowWidth = window.innerWidth;
                        if (this.windowWidth >= 1024) this.mobileOpen = false;
                    });
                },
                isDesktop() {
                    return this.windowWidth >= 1024;
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
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(24px) saturate(1.5);
            -webkit-backdrop-filter: blur(24px) saturate(1.5);
            border-right: 1px solid rgba(255,255,255,0.5);
            color: #1e293b;
            box-shadow: 4px 0 30px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            z-index: 30;
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), width 0.3s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .dark #sidebar {
            background: rgba(18, 18, 26, 0.75);
            border-right-color: rgba(255,255,255,0.05);
            box-shadow: 4px 0 30px rgba(0,0,0,0.3);
            color: #e2e8f0;
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

        /* Sidebar nav items */
        #sidebar nav a {
            position: relative;
            overflow: hidden;
        }
        #sidebar nav a::before {
            content: '';
            position: absolute;
            left: 0; top: 50%;
            width: 3px; height: 0;
            background: linear-gradient(180deg, #00d4ff, #8b5cf6);
            border-radius: 0 4px 4px 0;
            transition: height 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            transform: translateY(-50%);
        }
        #sidebar nav a.active-nav::before,
        #sidebar nav a:hover::before {
            height: 60%;
        }

        @media (min-width: 1024px) {
            #sidebar {
                transform: translateX(0);
                transition: width 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            }
            .content-wrapper {
                margin-left: 16rem;
                transition: margin-left 0.3s cubic-bezier(0.22, 1, 0.36, 1);
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
            .sidebar-collapsed #sidebar nav a::before {
                display: none;
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
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-20 bg-black/50 backdrop-blur-sm lg:hidden" @click="closeNav()" x-transition.opacity></div>

    <div id="sidebar" :class="{'open': mobileOpen}">
    <div class="flex items-center gap-3 px-4 h-16 border-b border-black/5 dark:border-white/5 shrink-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/20 sidebar-icon-wrap">
            <i class="fa-solid fa-boxes-stacked text-white text-sm"></i>
        </div>
        <div class="flex-1 min-w-0 sidebar-name">
            <h1 class="font-bold text-sm truncate tracking-tight gradient-text"><?= APP_NAME ?></h1>
        </div>
        <button @click="toggleSidebar()" class="flex p-1.5 rounded-lg text-gray-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-gray-600 dark:hover:text-gray-300 transition sidebar-toggle" title="Toggle sidebar">
            <i :class="isDesktop() ? (sidebarVisible ? 'fa-solid fa-chevron-left' : 'fa-solid fa-chevron-right') : 'fa-solid fa-xmark'" class="text-sm"></i>
        </button>
    </div>

    <div class="sidebar-body">
    <?php
    $navItems = [
        ['url' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
        ['url' => 'sparepart_keluar.php', 'label' => 'Sparepart Keluar', 'icon' => 'fa-right-from-bracket'],
        ['url' => 'my_approvals.php', 'label' => 'Approval Saya', 'icon' => 'fa-clipboard-check'],
        ['url' => 'history.php', 'label' => 'History', 'icon' => 'fa-clock-rotate-left'],
    ];
    $adminItems = [
        ['url' => 'jenis_sparepart.php', 'label' => 'Jenis & Type', 'icon' => 'fa-tags'],
        ['url' => 'ubah_status.php', 'label' => 'Ubah Status', 'icon' => 'fa-arrows-rotate'],
        ['url' => 'approval.php', 'label' => 'Approval', 'icon' => 'fa-check-double'],
        ['url' => 'users.php', 'label' => 'Kelola User', 'icon' => 'fa-users-gear'],
        ['url' => 'audit_logs.php', 'label' => 'Audit Log', 'icon' => 'fa-clipboard-list'],
    ];
    ?>
    <div class="px-3 pt-3 pb-1">
        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em] px-3 menu-label">Menu</p>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 pb-3 space-y-1">
        <?php
        $userPendingCount = 0;
        try {
            $userPendingStmt = getDB()->prepare("SELECT COUNT(*) FROM status_approvals WHERE status = 'pending' AND user_id = ? AND deleted_at IS NULL");
            $userPendingStmt->execute(array($user['id']));
            $userPendingCount = (int)$userPendingStmt->fetchColumn();
        } catch (Exception $e) { $userPendingCount = 0; }
        ?>
        <?php foreach ($navItems as $item):
            $active = $currentPage === str_replace('.php', '', $item['url']);
            $showUserBadge = ($item['url'] === 'my_approvals.php' && $userPendingCount > 0);
        ?>
        <a href="<?= pageUrl($item['url']) ?>" @click="closeNav()"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-all duration-200 <?= $active ? 'bg-blue-50 text-blue-600 dark:text-blue-400 shadow-[0_0_15px_rgba(37,99,235,0.08)] active-nav' : 'text-gray-600 dark:text-gray-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-gray-200' ?>">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 <?= $active ? 'bg-blue-500/10' : 'bg-transparent' ?>">
                <i class="fa-solid <?= $item['icon'] ?> text-center text-sm <?= $active ? 'text-blue-500 dark:text-blue-400' : '' ?>"></i>
            </div>
            <span><?= $item['label'] ?></span>
            <?php if ($showUserBadge): ?>
            <span class="ml-auto px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400"><?= $userPendingCount ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <?php if (isAdmin()): ?>
        <hr class="border-black/5 dark:border-white/5 my-2">
        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em] px-3 mb-1 admin-label">Admin</p>
        <?php
        $pendingApprovalCount = 0;
        if (isAdmin()) {
            try {
                $pendingStmt = getDB()->query("SELECT COUNT(*) FROM status_approvals WHERE status = 'pending'");
                $pendingApprovalCount = (int)$pendingStmt->fetchColumn();
            } catch (Exception $e) { $pendingApprovalCount = 0; }
        }
        ?>
        <?php foreach ($adminItems as $item):
            $active = $currentPage === str_replace('.php', '', $item['url']);
            $showBadge = ($item['url'] === 'approval.php' && $pendingApprovalCount > 0);
        ?>
        <a href="<?= pageUrl($item['url']) ?>" @click="closeNav()"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-all duration-200 <?= $active ? 'bg-blue-50 text-blue-600 dark:text-blue-400 shadow-[0_0_15px_rgba(37,99,235,0.08)] active-nav' : 'text-gray-600 dark:text-gray-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-gray-200' ?>">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 <?= $active ? 'bg-blue-500/10' : 'bg-transparent' ?>">
                <i class="fa-solid <?= $item['icon'] ?> text-center text-sm <?= $active ? 'text-blue-500 dark:text-blue-400' : '' ?>"></i>
            </div>
            <span><?= $item['label'] ?></span>
            <?php if ($showBadge): ?>
            <span class="ml-auto px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 admin-label"><?= $pendingApprovalCount ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <div class="border-t border-black/5 dark:border-white/5 p-3 space-y-2 shrink-0 bottom-section">
        <button @click="toggleDark()"
                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-[13px] font-medium text-gray-500 dark:text-gray-400 hover:bg-black/5 dark:hover:bg-white/5 transition-all duration-200 bottom-btn">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0">
                <i :class="isDark ? 'fa-solid fa-sun text-amber-400' : 'fa-solid fa-moon text-indigo-400'" class="text-sm"></i>
            </div>
            <span class="bottom-label" x-text="isDark ? 'Mode Terang' : 'Mode Gelap'"></span>
        </button>

        <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-black/3 dark:bg-white/3 bottom-section bottom-user">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-lg shadow-blue-500/20">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div class="bottom-label min-w-0 flex-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span class="text-[13px] font-semibold truncate text-gray-800 dark:text-gray-200"><?= escape($user['name']) ?></span>
                <span class="shrink-0"><?= getRoleBadge($user['role']) ?></span>
            </div>
        </div>
        <div class="flex gap-1 bottom-section">
            <a href="<?= pageUrl('profile.php') ?>" @click="closeNav()"
               class="bottom-btn flex-1 flex items-center justify-center gap-1.5 text-xs px-2 py-2 rounded-xl text-gray-400 hover:bg-black/5 dark:hover:bg-white/5 hover:text-gray-700 dark:hover:text-gray-300 transition-all duration-200">
                <i class="fa-solid fa-user"></i> <span class="bottom-label">Profil</span>
            </a>
            <a href="#" @click.prevent="showLogoutModal = true"
               class="bottom-btn flex-1 flex items-center justify-center gap-1.5 text-xs px-2 py-2 rounded-xl text-red-400 hover:bg-red-500/10 hover:text-red-500 transition-all duration-200">
                <i class="fa-solid fa-right-from-bracket"></i> <span class="bottom-label">Logout</span>
            </a>
        </div>
    </div>
</div>
</div>

    <div class="content-wrapper min-h-screen pt-4 lg:pt-6 p-4 lg:p-6 page-enter">
    <!-- Top bar: shown when sidebar is closed (mobile or desktop collapsed) -->
    <div x-show="isDesktop() ? !sidebarVisible : !mobileOpen" x-cloak
         class="flex items-center gap-3 px-4 h-14 mb-6 glass-panel-strong rounded-xl shadow-sm">
        <button @click="toggleSidebar()" class="lg:hidden p-2 -ml-1 rounded-lg text-gray-500 hover:bg-black/5 dark:hover:bg-white/5 transition">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-md shrink-0">
            <i class="fa-solid fa-boxes-stacked text-white text-sm"></i>
        </div>
        <h1 class="font-bold text-sm text-gray-800 dark:text-white"><?= APP_NAME ?></h1>
    </div>

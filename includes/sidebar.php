<?php $currentPage = $_GET['url'] ?? str_replace('.php', '', basename($_SERVER['PHP_SELF'])); ?>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sidebar', () => ({
            mobileOpen: false,
            isDark: localStorage.getItem('darkMode') === 'true',
            showLogoutModal: false,
            init() {
                if (this.isDark) document.documentElement.classList.add('dark');
            },
            toggleMobile() {
                this.mobileOpen = !this.mobileOpen;
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
<div x-data="sidebar()" class="flex h-screen overflow-hidden">
    <!-- Mobile overlay -->
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden backdrop-enter" @click="mobileOpen = false" x-transition.opacity></div>

    <!-- Mobile hamburger -->
    <button @click="toggleMobile()" class="fixed top-3 left-3 z-40 lg:hidden p-2 rounded-lg bg-white dark:bg-gray-800 shadow-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
        <i class="fa-solid fa-bars text-lg"></i>
    </button>

    <!-- Sidebar -->
    <div :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-primary-900 dark:bg-gray-950 text-white flex flex-col transition-transform duration-300 ease-in-out shadow-xl">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 h-16 border-b border-white/10 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-boxes-stacked text-white text-sm"></i>
                </div>
                <h1 class="font-bold text-base truncate"><?= APP_NAME ?></h1>
            </div>
            <button @click="toggleMobile()" class="lg:hidden p-1 rounded hover:bg-white/10 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto p-3 space-y-1">
            <a href="<?= pageUrl('dashboard.php') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentPage === 'dashboard' ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
                <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                <span>Dashboard</span>
            </a>
<a href="<?= pageUrl('ubah_status.php') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentPage === 'ubah_status' ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
                <i class="fa-solid fa-arrows-rotate w-5 text-center"></i>
                <span>Ubah Status</span>
            </a>
            <a href="<?= pageUrl('sparepart_keluar.php') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentPage === 'sparepart_keluar' ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
                <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>
                <span>Sparepart Keluar</span>
            </a>
            <a href="<?= pageUrl('history.php') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentPage === 'history' ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>
                <span>History</span>
            </a>
            <?php if (isAdmin()): ?>
            <hr class="border-white/10 my-2">
            <a href="<?= pageUrl('jenis_sparepart.php') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentPage === 'jenis_sparepart' ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
                <i class="fa-solid fa-tags w-5 text-center"></i>
                <span>Jenis & Type</span>
            </a>
            <a href="<?= pageUrl('users.php') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentPage === 'users' ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' ?>">
                <i class="fa-solid fa-users-gear w-5 text-center"></i>
                <span>Kelola User</span>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Bottom section -->
        <div class="border-t border-white/10 p-3 space-y-2 shrink-0">
            <!-- Dark mode toggle -->
            <button @click="toggleDark()" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white transition">
                <i :class="isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon'" class="w-5 text-center"></i>
                <span x-text="isDark ? 'Mode Terang' : 'Mode Gelap'"></span>
            </button>

            <!-- User info -->
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold shrink-0">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div class="min-w-0 flex-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span class="text-sm font-medium truncate"><?= escape($user['name']) ?></span>
                    <span class="shrink-0"><?= getRoleBadge($user['role']) ?></span>
                </div>
            </div>
            <div class="flex gap-1">
                <a href="#" @click.prevent="showLogoutModal = true" class="flex-1 flex items-center justify-center gap-1.5 text-xs px-2 py-1.5 rounded-lg text-red-300 hover:bg-red-500/20 hover:text-red-200 transition">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main content area -->
    <div class="flex-1 overflow-y-auto p-4 lg:p-6 pt-16 lg:pt-6 page-enter">
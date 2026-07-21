<?php require_once __DIR__ . '/../helpers/functions.php'; ?>
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

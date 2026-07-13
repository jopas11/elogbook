    </div>

    <!-- Logout Confirmation Modal -->
    <div x-show="showLogoutModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="showLogoutModal = false" x-transition.opacity>
        <div @click.stop class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center" x-transition.scale.90>
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <i class="fa-solid fa-right-from-bracket text-2xl text-red-500"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Yakin ingin keluar?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Anda akan keluar dari sesi saat ini.</p>
            <div class="flex gap-3">
                <button @click="showLogoutModal = false" class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Batal
                </button>
                <button @click="confirmLogout()" class="flex-1 px-4 py-2.5 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition inline-flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alpine store for flash -->
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('flash', {
            success: '<?= hasFlash('success') ? addslashes(flash('success')) : '' ?>',
            error: '<?= hasFlash('error') ? addslashes(flash('error')) : '' ?>',
        });
    });
</script>

<!-- Toast notifications -->
<div x-data="{ show: false, type: '', message: '' }"
     x-init="
         if (Alpine.store('flash').success) { type = 'success'; message = Alpine.store('flash').success; show = true; setTimeout(() => show = false, 4000); }
         if (Alpine.store('flash').error) { type = 'error'; message = Alpine.store('flash').error; show = true; setTimeout(() => show = false, 4000); }
     "
     x-show="show"
     x-cloak
     @click="show = false"
     :class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
     class="fixed top-4 right-4 z-50 max-w-sm w-full cursor-pointer toast-enter shadow-lg rounded-xl overflow-hidden">
    <div class="flex items-center gap-3 px-5 py-4">
        <div class="shrink-0">
            <i :class="type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation'" class="text-white text-lg"></i>
        </div>
        <p class="text-sm font-medium text-white flex-1" x-text="message"></p>
        <button @click.stop="show = false" class="text-white/70 hover:text-white transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <!-- Progress bar -->
    <div class="h-1 bg-white/30">
        <div class="h-full bg-white/60" :style="'width: 100%; animation: toast-progress 4s linear forwards;'"></div>
    </div>
</div>

<style>
    @keyframes toast-progress {
        from { width: 100%; }
        to { width: 0%; }
    }
</style>

<!-- Loading spinner component (hidden by default, triggered via event) -->
<div x-data="{ loading: false }"
     x-on:loading-start.window="loading = true"
     x-on:loading-end.window="loading = false"
     x-show="loading"
     x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black/30 backdrop-blur-sm"
     style="transition: opacity 0.2s ease;">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-2xl flex flex-col items-center gap-3">
        <div class="spinner text-primary-800 dark:text-primary-400" style="width: 2rem; height: 2rem; border-width: 3px;"></div>
        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Memproses...</p>
    </div>
</div>

</body>
</html>
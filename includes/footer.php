    </div>

    <div x-show="showLogoutModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showLogoutModal = false" x-transition.opacity>
        <div @click.stop class="glass-panel-strong rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center" x-transition.scale.90>
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-red-500/10 to-red-500/5 flex items-center justify-center ring-1 ring-red-500/20">
                <i class="fa-solid fa-right-from-bracket text-2xl text-red-500"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Yakin ingin keluar?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Anda akan keluar dari sesi saat ini.</p>
            <div class="flex gap-3">
                <button @click="showLogoutModal = false" class="flex-1 px-4 py-2.5 rounded-xl glass-panel text-gray-700 dark:text-gray-300 font-medium hover:bg-black/5 dark:hover:bg-white/5 transition">
                    Batal
                </button>
                <button @click="confirmLogout()" class="flex-1 px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-500 text-white font-medium hover:from-red-700 hover:to-rose-600 transition-all inline-flex items-center justify-center gap-2 shadow-lg shadow-red-500/20 magnetic-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('flash', {
            success: '<?= hasFlash('success') ? addslashes(flash('success')) : '' ?>',
            error: '<?= hasFlash('error') ? addslashes(flash('error')) : '' ?>',
        });
    });
</script>

<div x-data="{ show: false, type: '', message: '' }"
     x-init="
         if (Alpine.store('flash').success) { type = 'success'; message = Alpine.store('flash').success; show = true; setTimeout(() => show = false, 4000); }
         if (Alpine.store('flash').error) { type = 'error'; message = Alpine.store('flash').error; show = true; setTimeout(() => show = false, 4000); }
     "
     x-show="show"
     x-cloak
     @click="show = false"
     :class="type === 'success' ? 'bg-gradient-to-r from-emerald-600 to-teal-500' : 'bg-gradient-to-r from-red-600 to-rose-500'"
     class="fixed top-4 right-4 z-50 max-w-sm w-full cursor-pointer toast-enter shadow-xl shadow-black/20 rounded-2xl overflow-hidden border border-white/10">
    <div class="flex items-center gap-3 px-5 py-4">
        <div class="shrink-0">
            <i :class="type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation'" class="text-white text-lg"></i>
        </div>
        <p class="text-sm font-medium text-white flex-1" x-text="message"></p>
        <button @click.stop="show = false" class="text-white/70 hover:text-white transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="h-1 bg-white/20">
        <div class="h-full bg-white/60 rounded-full" :style="'width: 100%; animation: toast-progress 4s linear forwards;'"></div>
    </div>
</div>

<style>
    @keyframes toast-progress { from { width: 100%; } to { width: 0%; } }
    .zoom-image-popup .swal2-image { max-width: 80vw; max-height: 70vh; object-fit: contain; }
    @media (max-width: 640px) {
        .zoom-image-popup .swal2-image { max-width: 90vw; max-height: 60vh; }
    }
</style>

<div x-data="{ loading: false }"
     x-on:loading-start.window="loading = true"
     x-on:loading-end.window="loading = false"
     x-show="loading"
     x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm"
     style="transition: opacity 0.2s ease;">
    <div class="glass-panel-strong rounded-2xl p-8 shadow-2xl flex flex-col items-center gap-4">
        <div class="relative">
            <div class="spinner text-blue-600 dark:text-blue-400" style="width: 2.5rem; height: 2.5rem; border-width: 3px;"></div>
            <div class="absolute inset-0 spinner text-violet-500/30 dark:text-violet-400/30" style="width: 2.5rem; height: 2.5rem; border-width: 3px; animation-duration: 1.2s; animation-direction: reverse;"></div>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Memproses...</p>
    </div>
</div>

</body>
</html>

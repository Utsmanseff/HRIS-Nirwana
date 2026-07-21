{{-- Tombol aktifkan Web Push + pasang PWA. VAPID public key diexpose ke JS via meta di layout app.
     Tombol pasang default tersembunyi; ia muncul hanya bila browser mengirim
     beforeinstallprompt (Android/Chrome). iOS tak punya event itu — di sana tuntunan
     pemasangan muncul sebagai pesan saat tombol notifikasi ditekan. --}}
<div class="space-y-2">
    <button type="button" data-pwa-install class="btn btn-primary btn-sm gap-1.5 w-full justify-center hidden">
        <x-icon name="box" :size="16" />
        <span>Pasang Aplikasi</span>
    </button>
    <button type="button" data-push-subscribe class="btn btn-outline btn-sm gap-1.5 w-full justify-center">
        <x-icon name="bell" :size="16" />
        <span>Aktifkan Notifikasi</span>
    </button>
</div>

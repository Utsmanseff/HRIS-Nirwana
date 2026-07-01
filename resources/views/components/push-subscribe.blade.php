{{-- Tombol aktifkan Web Push. VAPID public key diexpose ke JS via meta di layout app. --}}
<button type="button" data-push-subscribe class="btn btn-outline btn-sm gap-1.5 w-full justify-center">
    <x-icon name="bell" :size="16" />
    <span>Aktifkan Notifikasi</span>
</button>

{{-- Modal konfirmasi global. Dikendalikan Alpine store 'konfirmasi'. --}}
<div
    x-data
    x-cloak
    x-show="$store.konfirmasi.tampil"
    x-transition.opacity
    @keydown.escape.window="$store.konfirmasi.tutup()"
    x-effect="document.body.style.overflow = $store.konfirmasi.tampil ? 'hidden' : ''"
    class="fixed inset-0 z-[100] grid place-items-center p-4"
    style="background:rgba(20,26,28,.45)"
>
    <div
        @click.outside="$store.konfirmasi.tutup()"
        x-effect="if ($store.konfirmasi.tampil) $nextTick(() => $refs.ya && $refs.ya.focus())"
        role="dialog" aria-modal="true"
        class="card shadow-lg w-full max-w-md rise"
        style="box-shadow:var(--shadow-lg)"
    >
        <div class="card-header">
            <div class="flex items-center gap-3">
                <template x-if="$store.konfirmasi.varian === 'danger'">
                    <span class="w-9 h-9 rounded-lg bg-danger-50 text-danger-600 grid place-items-center">
                        <svg width="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l9 16H3z" stroke-linejoin="round"/><path d="M12 10v4M12 17h.01" stroke-linecap="round"/></svg>
                    </span>
                </template>
                <template x-if="$store.konfirmasi.varian !== 'danger'">
                    <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 grid place-items-center">
                        <svg width="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </template>
                <span class="card-title" x-text="$store.konfirmasi.judul"></span>
            </div>
            <button class="btn btn-ghost btn-icon" aria-label="Tutup" @click="$store.konfirmasi.tutup()">
                <svg viewBox="0 0 24 24" width="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="card-pad">
            <p class="text-sm text-neutral-600 leading-relaxed" x-text="$store.konfirmasi.pesan"></p>
        </div>
        <div class="card-pad pt-0 flex justify-end gap-3">
            <button class="btn btn-secondary" x-show="$store.konfirmasi.mode === 'konfirmasi'" @click="$store.konfirmasi.tutup()">Batal</button>
            <button
                x-ref="ya"
                :class="$store.konfirmasi.varian === 'danger' ? 'btn btn-danger' : 'btn btn-primary'"
                x-text="$store.konfirmasi.labelYa"
                @click="$store.konfirmasi.setuju()"
            ></button>
        </div>
    </div>
</div>

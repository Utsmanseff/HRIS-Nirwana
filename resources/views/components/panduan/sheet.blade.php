{{-- Sheet panduan — dirender SEKALI per halaman (dipasang di layouts.app sebaris
     x-konfirmasi). Tombol "?" di appbar & topbar sama-sama menyetir store ini,
     sehingga tak ada dua dialog kembar di DOM. --}}
@if (\App\Support\Panduan::untukRute(request()->route()?->getName()))
    <div x-data x-cloak x-show="$store.tanyaPanduan.buka" @keydown.escape.window="$store.tanyaPanduan.tutup()">
        <div class="ptny-tirai" @click="$store.tanyaPanduan.tutup()"></div>

        <div class="ptny-sheet" role="dialog" aria-modal="true" aria-labelledby="ptny-judul"
             @keydown.tab="$store.tanyaPanduan.jagaFokus($event)">
            <div class="ptny-kepala">
                <div class="min-w-0">
                    <div class="ptny-eyebrow">Panduan · <span x-text="$store.tanyaPanduan.bab"></span></div>
                    <div id="ptny-judul" class="ptny-judul" x-text="$store.tanyaPanduan.judul"></div>
                </div>
                <button type="button" class="ptny-tutup"
                        @click="$store.tanyaPanduan.tutup()" aria-label="Tutup panduan">
                    <x-icon name="x" :size="18" />
                </button>
            </div>

            <div class="ptny-isi">
                <p class="ptny-status" x-show="$store.tanyaPanduan.memuat">Memuat panduan…</p>
                <p class="ptny-status" x-show="$store.tanyaPanduan.gagal" x-cloak>
                    Panduan gagal dimuat. Periksa koneksi, lalu coba lagi.
                </p>
                <div x-show="!$store.tanyaPanduan.memuat && !$store.tanyaPanduan.gagal"
                     x-html="$store.tanyaPanduan.isi"></div>
            </div>

            <div class="ptny-kaki">
                <div class="ptny-chips">
                    <template x-for="b in $store.tanyaPanduan.lain" :key="b.id">
                        <button type="button" class="ptny-chip"
                                :aria-current="b.id === $store.tanyaPanduan.bagian ? 'true' : 'false'"
                                x-text="b.judul"
                                @click="$store.tanyaPanduan.tampil($store.tanyaPanduan.slug, b.id)"></button>
                    </template>
                </div>
                <a class="ptny-lengkap"
                   :href="`/panduan/${$store.tanyaPanduan.slug}#${$store.tanyaPanduan.bagian}`">Panduan lengkap →</a>
            </div>
        </div>
    </div>
@endif

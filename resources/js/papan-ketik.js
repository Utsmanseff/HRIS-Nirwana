// Sembunyikan bottom-nav selama papan ketik virtual terbuka.
//
// .m-nav memakai `position:sticky; bottom:0`, yang mengacu ke LAYOUT viewport. Saat
// papan ketik iOS muncul, yang mengecil hanyalah VISUAL viewport — layout viewport tetap
// setinggi layar. Akibatnya nav berhenti di garis bawah layar yang kini tertutup papan
// ketik, lalu tampak mengambang di tengah-tengah dan menutupi kolom isian.
//
// Android Chrome tidak butuh ini: papan ketiknya ikut mengecilkan layout viewport, jadi
// nav sudah duduk tepat di atas papan ketik. Itu juga sebabnya deteksinya memakai selisih
// innerHeight lawan visualViewport — di Android selisihnya tetap ~0 sehingga tak aktif.

const AMBANG_PX = 150;

function setel(terbuka) {
    document.body.classList.toggle('papan-ketik-terbuka', terbuka);
}

const layarVisual = window.visualViewport;

if (layarVisual) {
    const periksa = () => setel(window.innerHeight - layarVisual.height > AMBANG_PX);

    layarVisual.addEventListener('resize', periksa);
    document.addEventListener('livewire:navigated', periksa);
} else {
    // Peramban lawas tanpa visualViewport: andalkan fokus kolom isian.
    document.addEventListener('focusin', (e) => {
        if (e.target.matches('input:not([type="checkbox"]):not([type="radio"]), textarea, select')) {
            setel(true);
        }
    });
    document.addEventListener('focusout', () => setel(false));
}

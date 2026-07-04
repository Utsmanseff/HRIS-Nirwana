<?php

// Identitas instansi untuk kop laporan/ekspor.
// SEMENTARA di config — pindah ke tabel pengaturan saat halaman
// Pengaturan (Admin Sistem) dibangun. Ubah di sini = semua kop ikut.
return [
    'nama' => 'RSU Nirwana', // pendek — alt logo / penamaan singkat
    // Baris kop (rata tengah di PDF & Excel):
    'nama_resmi' => 'RUMAH SAKIT UMUM NIRWANA',
    'alamat' => 'Jl. Panglima Batur Timur No. 42 Banjarbaru Kalimantan Selatan',
    'telp' => 'Telp. 0511-674 9722 / 0821 5084 1882',
    'email_web' => 'Email: official@rsunirwana.id | Website: https://rsunirwana.id',
    'logo' => 'img/RSU22Nirwana.png', // relatif public/, khusus kop
    // TODO: logo akreditasi belum ada filenya — taruh di public/img/ lalu isi path di sini.
    // Selagi null, layout PDF render kotak placeholder bergaris di kanan header.
    'logo_akreditasi' => null,
];

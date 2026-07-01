<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            // SIP (Surat Izin Praktik) — hanya relevan untuk nakes, semua nullable.
            // 1 baris aktif per karyawan (di-overwrite saat perpanjang, bukan riwayat).
            $table->string('sip_nomor')->nullable()->after('email');
            $table->date('sip_berlaku_mulai')->nullable()->after('sip_nomor');
            $table->date('sip_berlaku_akhir')->nullable()->after('sip_berlaku_mulai');
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropColumn(['sip_nomor', 'sip_berlaku_mulai', 'sip_berlaku_akhir']);
        });
    }
};

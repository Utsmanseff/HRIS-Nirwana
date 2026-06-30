<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('karyawan_id')->nullable()->unique()->after('id')->constrained('karyawan')->nullOnDelete();
            $t->string('google_id')->nullable()->unique()->after('email');
            $t->string('avatar_url')->nullable()->after('google_id');
            $t->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('karyawan_id');
            $t->dropColumn(['google_id', 'avatar_url']);
        });
    }
};

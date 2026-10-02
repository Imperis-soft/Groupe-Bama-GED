<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copie officielle : document en PDF + certificat de validation (QR, approbations, signatures)
        Schema::table('documents', function (Blueprint $table) {
            $table->string('official_copy_path')->nullable()->after('archival_copy_checksum');
            $table->char('official_copy_checksum', 64)->nullable()->after('official_copy_path');
            $table->unsignedInteger('official_copy_version')->nullable()->after('official_copy_checksum');
            $table->timestamp('official_copy_at')->nullable()->after('official_copy_version');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['official_copy_path', 'official_copy_checksum', 'official_copy_version', 'official_copy_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Copie d'archivage PDF/A créée à l'archivage (l'original est conservé tel quel)
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('archival_copy_path')->nullable()->after('file_path');
            $table->char('archival_copy_checksum', 64)->nullable()->after('archival_copy_path');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['archival_copy_path', 'archival_copy_checksum']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            // Empreinte SHA256 du fichier au moment de la signature
            $table->string('document_checksum', 64)->nullable()->after('signature_hash');
            $table->unsignedInteger('document_version')->nullable()->after('document_checksum');
            // Un PNG en base64 dépasse vite 64 Ko (limite du TEXT MySQL)
            $table->longText('signature_data')->change();
            $table->text('user_agent')->nullable()->change();
        });

        // Les user-agents modernes dépassent souvent 255 caractères
        foreach (['document_audit_logs', 'login_histories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->text('user_agent')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->dropColumn(['document_checksum', 'document_version']);
        });
    }
};

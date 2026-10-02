<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cycle de vie : brouillon → en approbation → approuvé → en signature → signé → archivé
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_status_check');
        }
        Schema::table('documents', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->change();
        });

        // Règles par catégorie (null : celle de la catégorie parente)
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('requires_approval')->nullable()->after('final_disposition');
            $table->boolean('requires_signature')->nullable()->after('requires_approval');
        });

        // Preuve de ce qui a été approuvé, et forçage par un administrateur
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->unsignedInteger('document_version')->nullable()->after('decided_at');
            $table->string('document_checksum', 64)->nullable()->after('document_version');
            $table->foreignId('forced_by_id')->nullable()->after('document_checksum')->constrained('users')->nullOnDelete();
            $table->text('force_reason')->nullable()->after('forced_by_id');
        });

        // Relances des signatures en retard
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('reminders_sent')->default(0)->after('due_at');
            $table->timestamp('last_reminded_at')->nullable()->after('reminders_sent');
        });
    }

    public function down(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropColumn(['reminders_sent', 'last_reminded_at']);
        });
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forced_by_id');
            $table->dropColumn(['document_version', 'document_checksum', 'force_reason']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['requires_approval', 'requires_signature']);
        });
    }
};

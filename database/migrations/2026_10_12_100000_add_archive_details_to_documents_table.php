<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Qui a archivé, pourquoi, et le statut à rétablir en cas de désarchivage
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            $table->string('archive_reason', 500)->nullable()->after('archived_by');
            $table->string('status_before_archive', 20)->nullable()->after('archive_reason');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn(['archive_reason', 'status_before_archive']);
        });
    }
};

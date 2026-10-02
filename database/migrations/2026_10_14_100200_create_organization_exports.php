<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Exports complets d'une entreprise (réversibilité : récupérer toutes ses données)
    public function up(): void
    {
        Schema::create('organization_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 10)->default('pending'); // pending | running | done | failed
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->char('checksum', 64)->nullable();
            $table->unsignedInteger('documents_count')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_exports');
    }
};

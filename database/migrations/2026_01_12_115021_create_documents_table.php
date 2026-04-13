<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->string('reference')->unique(); // Ex: GB-2024-001
        $table->string('title');
        $table->string('file_path'); // Chemin exact dans MinIO
        $table->string('mime_type')->default('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $table->integer('version')->default(1);
        $table->jsonb('metadata')->nullable(); // PostgreSQL adore le JSONB pour les infos extra
        $table->foreignId('user_id')->nullable()->constrained(); // Si tu as une auth plus tard
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

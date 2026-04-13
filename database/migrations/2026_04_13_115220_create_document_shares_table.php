<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_with')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('share_token')->unique()->nullable(); // pour partage par lien
            $table->enum('access_level', ['view', 'edit', 'comment'])->default('view');
            $table->string('message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};

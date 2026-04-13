<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('document_comments')->onDelete('cascade');
            $table->text('content');
            $table->enum('type', ['comment', 'annotation', 'approval_note', 'rejection_note'])->default('comment');
            $table->boolean('is_internal')->default(false); // visible seulement par les admins
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_comments');
    }
};

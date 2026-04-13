<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade')->unique();
            $table->foreignId('locked_by')->constrained('users')->onDelete('cascade');
            $table->string('lock_token')->unique();
            $table->timestamp('locked_at');
            $table->timestamp('expires_at'); // auto-release après X minutes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_locks');
    }
};

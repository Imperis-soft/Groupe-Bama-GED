<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('signature_data'); // base64 de la signature dessinée
            $table->string('signature_hash'); // hash SHA256 pour vérification
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('page_number')->nullable();
            $table->json('position')->nullable(); // {x, y, width, height} sur le doc
            $table->enum('status', ['pending', 'signed', 'rejected'])->default('signed');
            $table->text('reason')->nullable(); // raison de la signature
            $table->timestamp('signed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};

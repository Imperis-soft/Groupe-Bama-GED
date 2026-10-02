<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Demandes de démo / devis reçues depuis la page d'accueil
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('formula', ['saas', 'single_entity', 'unsure'])->default('unsure');
            $table->string('company');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company_size')->nullable();
            $table->string('sector')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'won', 'lost'])->default('new');
            $table->text('notes')->nullable(); // suivi commercial interne
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};

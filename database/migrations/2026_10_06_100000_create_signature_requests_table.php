<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Demandes de signature adressées à un utilisateur
        Schema::create('signature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // signataire
            $table->text('message')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending | signed | declined | cancelled
            $table->foreignId('signature_id')->nullable()->constrained('document_signatures')->nullOnDelete();
            $table->text('decline_reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_requests');
    }
};

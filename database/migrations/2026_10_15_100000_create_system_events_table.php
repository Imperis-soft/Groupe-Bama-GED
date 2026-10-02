<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal système (console super admin) : erreurs, échecs de tâches, d'envoi d'emails, de stockage,
     * alertes d'intégrité et résultats des contrôles de santé. Un même problème est regroupé
     * (empreinte « fingerprint ») et compté au lieu d'être répété.
     */
    public function up(): void
    {
        Schema::create('system_events', function (Blueprint $table) {
            $table->id();
            $table->string('level', 10);       // info | warning | error | critical
            $table->string('category', 20);    // application | storage | database | queue | mail | integrity | scheduler | processing | system
            $table->string('message', 1000);
            $table->json('context')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->char('fingerprint', 64);
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fingerprint', 'resolved_at']);
            $table->index(['resolved_at', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_events');
    }
};

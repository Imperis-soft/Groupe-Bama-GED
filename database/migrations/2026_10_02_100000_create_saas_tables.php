<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Entreprises clientes (tenants)
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('reference_prefix', 10)->default('DOC'); // préfixe des références : PREFIXE-XXXXXX
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->string('suspended_reason')->nullable();
            $table->text('notes')->nullable(); // notes internes Imperis
            $table->timestamps();
        });

        // Offres commerciales
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);          // prix par période, en unités de la devise
            $table->string('currency', 3)->default('XOF');
            $table->unsignedTinyInteger('billing_months')->default(1); // durée d'une période
            $table->unsignedInteger('max_users')->nullable();      // null = illimité
            $table->unsignedInteger('max_storage_mb')->nullable(); // null = illimité
            $table->json('features')->nullable();                  // liste d'arguments commerciaux
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Abonnements (une ligne par période payée)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['trial', 'active', 'cancelled'])->default('active');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->unsignedInteger('amount')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->string('payment_method')->nullable();    // virement, orange_money, wave, especes…
            $table->string('payment_reference')->nullable();
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'ends_at']);
        });

        // Journal des actions du super admin
        Schema::create('platform_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // Offres par défaut (modifiables depuis l'espace super admin)
        $now = now();
        DB::table('plans')->insert([
            [
                'name' => 'Essentiel', 'slug' => 'essentiel', 'price' => 25000, 'currency' => 'XOF', 'billing_months' => 1,
                'max_users' => 5, 'max_storage_mb' => 5120, 'sort_order' => 1, 'is_active' => true,
                'description' => 'Pour les petites équipes',
                'features' => json_encode(['5 utilisateurs', '5 Go de stockage', 'Versions et QR codes', 'Support par email']),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Pro', 'slug' => 'pro', 'price' => 75000, 'currency' => 'XOF', 'billing_months' => 1,
                'max_users' => 20, 'max_storage_mb' => 51200, 'sort_order' => 2, 'is_active' => true,
                'description' => 'Pour les PME',
                'features' => json_encode(['20 utilisateurs', '50 Go de stockage', 'Workflow d\'approbation', 'Signatures électroniques']),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Entreprise', 'slug' => 'entreprise', 'price' => 200000, 'currency' => 'XOF', 'billing_months' => 1,
                'max_users' => null, 'max_storage_mb' => null, 'sort_order' => 3, 'is_active' => true,
                'description' => 'Pour les groupes et grandes structures',
                'features' => json_encode(['Utilisateurs illimités', 'Stockage illimité', 'Legal Hold et archivage avancé', 'Support prioritaire']),
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_activity_logs');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('organizations');
    }
};

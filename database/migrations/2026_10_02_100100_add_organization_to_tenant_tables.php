<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('password');
            $table->boolean('is_active')->default(true)->after('is_super_admin');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Catégories : noms uniques par entreprise et non plus globalement
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropUnique(['slug']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['organization_id', 'name']);
            $table->unique(['organization_id', 'slug']);
        });

        // Paramètres : par entreprise (organization_id NULL = paramètres de la plateforme)
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['organization_id', 'key']);
        });

        // Notifications sans objet lié (ex. renouvellement d'abonnement)
        Schema::table('ged_notifications', function (Blueprint $table) {
            $table->string('notifiable_type')->nullable()->change();
            $table->unsignedBigInteger('notifiable_id')->nullable()->change();
        });

        $this->migrateExistingInstallation();
    }

    /**
     * Les données déjà présentes (installation Groupe Bama) deviennent la première entreprise,
     * et la licence en cours devient son abonnement.
     */
    private function migrateExistingInstallation(): void
    {
        DB::table('users')->where('email', config('saas.super_admin_email'))->update(['is_super_admin' => true]);

        $hasData = DB::table('users')->where('is_super_admin', false)->exists()
            || DB::table('documents')->exists()
            || DB::table('categories')->exists();

        if ($hasData) {
            $now   = now();
            $orgId = DB::table('organizations')->insertGetId([
                'name' => 'Groupe Bama', 'slug' => 'groupe-bama', 'reference_prefix' => 'BAMA',
                'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ]);

            DB::table('users')->where('is_super_admin', false)->update(['organization_id' => $orgId]);
            DB::table('documents')->update(['organization_id' => $orgId]);
            DB::table('categories')->update(['organization_id' => $orgId]);
            DB::table('settings')->update(['organization_id' => $orgId]);
            DB::table('document_audit_logs')->update(['organization_id' => $orgId]);

            $license = Schema::hasTable('licenses')
                ? DB::table('licenses')->where('is_active', true)->orderByDesc('expires_at')->first()
                : null;

            DB::table('subscriptions')->insert([
                'organization_id' => $orgId,
                'plan_id'         => DB::table('plans')->where('slug', 'entreprise')->value('id'),
                'status'          => 'active',
                'starts_at'       => $now->toDateString(),
                'ends_at'         => $license?->expires_at ?? $now->copy()->addYear()->toDateString(),
                'amount'          => 0,
                'payment_method'  => 'licence',
                'payment_reference' => $license?->license_key,
                'notes'           => 'Reprise de la licence existante lors du passage en SaaS',
                'created_at'      => $now, 'updated_at' => $now,
            ]);
        }

        // Le système de licence est remplacé par les abonnements
        Schema::dropIfExists('licenses');
    }

    public function down(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->string('licensed_to')->default('Groupe Bama');
            $table->string('issued_by')->default('Imperis SARL');
            $table->date('issued_at');
            $table->date('expires_at');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'key']);
            $table->dropConstrainedForeignId('organization_id');
            $table->unique('key');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'name']);
            $table->dropUnique(['organization_id', 'slug']);
            $table->dropConstrainedForeignId('organization_id');
            $table->unique('name');
            $table->unique('slug');
        });
        Schema::table('document_audit_logs', fn (Blueprint $table) => $table->dropConstrainedForeignId('organization_id'));
        Schema::table('documents', fn (Blueprint $table) => $table->dropConstrainedForeignId('organization_id'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['is_super_admin', 'is_active']);
        });
    }
};

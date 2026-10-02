<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Journal d'audit chaîné (par entreprise) : chaque ligne porte l'empreinte de la précédente.
     * Modifier, supprimer ou insérer une ligne après coup casse la chaîne, ce que détecte `audit:verify`.
     * - user_name : nom de l'auteur recopié (la preuve ne dépend pas d'un compte qui peut être supprimé) ;
     * - sequence  : ordre de scellement dans l'entreprise ;
     * - hash / previous_hash : empreintes SHA-256.
     */
    public function up(): void
    {
        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
            $table->unsignedBigInteger('sequence')->nullable()->after('user_agent');
            $table->char('previous_hash', 64)->nullable()->after('sequence');
            $table->char('hash', 64)->nullable()->after('previous_hash');
            $table->index(['organization_id', 'sequence']);
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('audit_head_sequence')->nullable();
            $table->char('audit_head_hash', 64)->nullable();
        });

        // Lignes existantes : auteur recopié (scellement ensuite par App\Support\AuditChain)
        DB::table('users')->orderBy('id')->select('id', 'full_name')->chunk(500, function ($users) {
            foreach ($users as $user) {
                DB::table('document_audit_logs')->where('user_id', $user->id)->whereNull('user_name')->update(['user_name' => $user->full_name]);
            }
        });

        foreach (DB::table('document_audit_logs')->distinct()->pluck('organization_id') as $organizationId) {
            \App\Support\AuditChain::seal($organizationId);
        }
    }

    public function down(): void
    {
        // MySQL : l'index (organization_id, sequence) a pu remplacer celui de la clé étrangère organization_id ;
        // un index dédié doit exister avant de le supprimer
        if (!collect(Schema::getIndexes('document_audit_logs'))->contains(fn ($index) => $index['columns'] === ['organization_id'])) {
            Schema::table('document_audit_logs', fn (Blueprint $table) => $table->index('organization_id'));
        }
        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'sequence']);
            $table->dropColumn(['user_name', 'sequence', 'previous_hash', 'hash']);
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['audit_head_sequence', 'audit_head_hash']);
        });
    }
};

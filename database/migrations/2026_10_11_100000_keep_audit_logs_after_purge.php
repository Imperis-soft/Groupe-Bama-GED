<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le journal d'audit doit survivre à la suppression définitive d'un document :
     * c'est la preuve qu'il a existé, de ce qui lui est arrivé et de qui l'a détruit.
     * - document_id devient facultatif (mis à NULL à la purge au lieu de supprimer les lignes) ;
     * - la référence et le titre du document sont recopiés dans chaque ligne.
     */
    public function up(): void
    {
        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->string('document_reference')->nullable()->after('document_id');
            $table->string('document_title')->nullable()->after('document_reference');
        });

        // MySQL : la clé étrangère doit être retirée avant de modifier la colonne
        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });
        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable()->change();
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete();
        });

        // Lignes existantes : recopie de la référence et du titre
        DB::table('documents')->orderBy('id')->select('id', 'reference', 'title')->chunk(500, function ($documents) {
            foreach ($documents as $document) {
                DB::table('document_audit_logs')->where('document_id', $document->id)
                    ->update(['document_reference' => $document->reference, 'document_title' => $document->title]);
            }
        });
    }

    public function down(): void
    {
        // Les lignes orphelines (documents purgés) ne peuvent pas retrouver de document : elles sont supprimées
        DB::table('document_audit_logs')->whereNull('document_id')->delete();

        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });
        Schema::table('document_audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable(false)->change();
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->dropColumn(['document_reference', 'document_title']);
        });
    }
};

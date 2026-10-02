<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dossier propre à chaque entreprise dans le bucket partagé (ex. ged/groupe-bama/).
        // Fixé à la création et jamais modifié : les chemins des fichiers en dépendent.
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('storage_folder', 100)->nullable()->unique()->after('storage_bucket');
        });

        // Entreprises existantes : on garde le dossier org-{id}/ déjà utilisé par leurs fichiers
        DB::table('organizations')->whereNull('storage_folder')->pluck('id')->each(
            fn ($id) => DB::table('organizations')->where('id', $id)->update(['storage_folder' => 'org-' . $id])
        );
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['storage_folder']);
            $table->dropColumn('storage_folder');
        });
    }
};

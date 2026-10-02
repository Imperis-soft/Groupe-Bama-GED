<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stockage MinIO propre à chaque entreprise.
        // Champ vide = valeur du serveur MinIO de la plateforme (.env AWS_*).
        // storage_bucket NULL = ancien mode : bucket partagé de la plateforme, dossier org-{id}/.
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('storage_bucket', 63)->nullable()->after('notes');
            $table->string('storage_endpoint')->nullable()->after('storage_bucket');
            $table->string('storage_url')->nullable()->after('storage_endpoint');
            $table->string('storage_region', 50)->nullable()->after('storage_url');
            $table->string('storage_key')->nullable()->after('storage_region');
            $table->text('storage_secret')->nullable()->after('storage_key'); // chiffré (cast encrypted)
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['storage_bucket', 'storage_endpoint', 'storage_url', 'storage_region', 'storage_key', 'storage_secret']);
        });
    }
};

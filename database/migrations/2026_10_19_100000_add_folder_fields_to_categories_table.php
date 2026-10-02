<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dossiers créés par les utilisateurs : on retient l'auteur (renommage / suppression d'un dossier vide)
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('parent_id')->constrained('users')->nullOnDelete();
        });

        // Noms uniques dans un même dossier parent (« 2025 » peut exister sous plusieurs dossiers).
        // Nouvel index créé avant la suppression de l'ancien : la clé étrangère organization_id garde un index.
        Schema::table('categories', function (Blueprint $table) {
            $table->unique(['organization_id', 'parent_id', 'name']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unique(['organization_id', 'name']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'parent_id', 'name']);
            $table->dropConstrainedForeignId('created_by');
        });
    }
};

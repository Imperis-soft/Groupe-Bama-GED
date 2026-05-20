<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter le Legal Hold et les champs d'archivage avancé sur documents
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('legal_hold')->default(false)->after('is_confidential');
            $table->timestamp('legal_hold_at')->nullable()->after('legal_hold');
            $table->unsignedBigInteger('legal_hold_by')->nullable()->after('legal_hold_at');
            $table->string('legal_hold_reason')->nullable()->after('legal_hold_by');

            $table->foreign('legal_hold_by')->references('id')->on('users')->nullOnDelete();
        });

        // Ajouter la politique de rétention par catégorie
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('default_retention_years')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['legal_hold_by']);
            $table->dropColumn(['legal_hold', 'legal_hold_at', 'legal_hold_by', 'legal_hold_reason']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('default_retention_years');
        });
    }
};

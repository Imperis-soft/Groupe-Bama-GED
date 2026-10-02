<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Services / départements de l'entreprise (RH, Comptabilité, Juridique…)
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        // Membres d'un service (un utilisateur peut appartenir à plusieurs services)
        Schema::create('department_user', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['department_id', 'user_id']);
        });

        // Droits d'un service sur une catégorie (hérités par les sous-catégories)
        Schema::create('category_department', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('access_level', 10)->default('view'); // view | edit
            $table->primary(['category_id', 'department_id']);
        });

        // Catégorie consultable par tous les membres de l'entreprise
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('default_retention_years');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
        Schema::dropIfExists('category_department');
        Schema::dropIfExists('department_user');
        Schema::dropIfExists('departments');
    }
};

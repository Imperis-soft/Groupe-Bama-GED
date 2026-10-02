<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Les services deviennent un catalogue commun à toutes les entreprises, géré par le super admin.
     * Ce qui reste propre à chaque entreprise :
     *  - les membres (department_user : les utilisateurs appartiennent à une entreprise) ;
     *  - les droits sur les catégories (category_department : les catégories appartiennent à une entreprise) ;
     *  - le responsable du service (nouvelle table department_organization).
     */
    public function up(): void
    {
        // Relançable : sur MySQL, une exécution interrompue laisse les changements déjà faits (DDL non transactionnel)
        if (!Schema::hasTable('department_organization')) {
            Schema::create('department_organization', function (Blueprint $table) {
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->primary(['department_id', 'organization_id']);
            });
        }
        if (!Schema::hasColumn('departments', 'organization_id')) {
            return;
        }

        // Fusion des services de même nom (insensible à la casse et aux accents) en un seul service commun
        $keep = [];
        foreach (DB::table('departments')->orderBy('id')->get() as $department) {
            $key = Str::slug($department->name);
            $targetId = $keep[$key] ??= $department->id;

            if ($department->manager_id) {
                DB::table('department_organization')->updateOrInsert(
                    ['department_id' => $targetId, 'organization_id' => $department->organization_id],
                    ['manager_id' => $department->manager_id, 'created_at' => now(), 'updated_at' => now()]
                );
            }
            if ($targetId === $department->id) {
                continue;
            }

            foreach (DB::table('department_user')->where('department_id', $department->id)->pluck('user_id') as $userId) {
                DB::table('department_user')->insertOrIgnore(['department_id' => $targetId, 'user_id' => $userId]);
            }
            foreach (DB::table('category_department')->where('department_id', $department->id)->get() as $rule) {
                DB::table('category_department')->insertOrIgnore([
                    'department_id' => $targetId, 'category_id' => $rule->category_id, 'access_level' => $rule->access_level,
                ]);
            }
            DB::table('departments')->where('id', $department->id)->delete();
        }

        // MySQL : l'index unique (organization_id, name) sert aussi la clé étrangère organization_id,
        // il faut donc retirer les clés étrangères avant l'index, puis les colonnes
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['manager_id']);
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'name']);
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['organization_id', 'manager_id']);
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['organization_id', 'name']);
        });
        Schema::dropIfExists('department_organization');
    }
};

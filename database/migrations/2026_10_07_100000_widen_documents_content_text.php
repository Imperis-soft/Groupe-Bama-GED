<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Sous MySQL, TEXT est limité à 64 Ko : le texte extrait d'un document volumineux ne tenait pas.
    // (PostgreSQL : TEXT est déjà illimité ; SQLite : pas de limite.)
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('documents', function (Blueprint $table) {
                $table->longText('content_text')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('documents', function (Blueprint $table) {
                $table->text('content_text')->nullable()->change();
            });
        }
    }
};

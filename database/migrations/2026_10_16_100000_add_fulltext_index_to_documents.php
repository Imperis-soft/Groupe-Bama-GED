<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Index FULLTEXT MySQL pour la recherche (PostgreSQL utilise déjà l'index GIN to_tsvector)
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('documents', function (Blueprint $table) {
                $table->fullText(['title', 'reference', 'content_text'], 'documents_fulltext_search');
            });
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropFullText('documents_fulltext_search');
            });
        }
    }
};

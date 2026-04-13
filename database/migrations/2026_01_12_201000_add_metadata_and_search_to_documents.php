<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'tags')) {
                $table->json('tags')->nullable()->after('metadata');
            }
            if (!Schema::hasColumn('documents', 'content_text')) {
                $table->text('content_text')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('documents', 'search_vector')) {
                $table->text('search_vector')->nullable()->after('content_text');
            }
        });

        // Triggers/functions only supported on PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE FUNCTION documents_search_vector_update() RETURNS trigger AS $$
                begin
                  new.search_vector := to_tsvector('french', coalesce(new.title,'') || ' ' || coalesce(new.reference,'') || ' ' || coalesce(new.content_text,'') || ' ' || coalesce((new.metadata->>'original_name'),'') );
                  return new;
                end
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER documents_search_vector_trigger
                BEFORE INSERT OR UPDATE ON documents
                FOR EACH ROW EXECUTE PROCEDURE documents_search_vector_update();
            SQL);

            DB::statement("CREATE INDEX IF NOT EXISTS documents_search_vector_gin ON documents USING GIN (to_tsvector('french', coalesce(title,'') || ' ' || coalesce(reference,'') || ' ' || coalesce(content_text,'') || ' ' || coalesce((metadata->>'original_name'),'') ));");
        }
    }

    public function down()
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS documents_search_vector_trigger ON documents;');
            DB::unprepared('DROP FUNCTION IF EXISTS documents_search_vector_update();');
            DB::statement('DROP INDEX IF EXISTS documents_search_vector_gin');
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['tags', 'content_text', 'search_vector'],
                fn($col) => Schema::hasColumn('documents', $col)
            ));
        });
    }
};

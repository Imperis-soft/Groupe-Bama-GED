<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fin de conservation (DUA) et sort final :
     * - catégorie : point de départ du délai et sort final (détruire, conserver, réexaminer) ;
     * - document : date de fin de conservation calculée, conservation définitive décidée ;
     * - procès-verbaux d'élimination : trace permanente de chaque destruction validée.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // created | archived | due_date | fiscal_year_end
            $table->string('retention_trigger', 20)->default('created')->after('default_retention_years');
            // destroy | keep | review
            $table->string('final_disposition', 10)->default('review')->after('retention_trigger');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->date('retention_until')->nullable()->after('retention_years')->index();
            $table->boolean('retention_permanent')->default(false)->after('retention_until');
        });

        Schema::create('elimination_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approved_by_name');
            $table->text('reason');
            $table->json('documents'); // instantané : référence, titre, catégorie, dates, empreinte…
            $table->unsignedInteger('documents_count');
            $table->string('pdf_path')->nullable();
            $table->char('pdf_checksum', 64)->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elimination_records');
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['retention_until']);
            $table->dropColumn(['retention_until', 'retention_permanent']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['retention_trigger', 'final_disposition']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Contrôles d'intégrité : état par document et historique des contrôles par entreprise
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('integrity_status', 10)->nullable()->after('checksum'); // ok | altered | missing
            $table->timestamp('integrity_checked_at')->nullable()->after('integrity_status');
        });

        Schema::create('integrity_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('files_checked')->default(0);
            $table->unsignedInteger('files_failed')->default(0);
            $table->unsignedInteger('baselines')->default(0); // fichiers sans empreinte : empreinte enregistrée
            $table->boolean('audit_chain_ok')->default(true);
            $table->json('problems')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrity_checks');
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['integrity_status', 'integrity_checked_at']);
        });
    }
};

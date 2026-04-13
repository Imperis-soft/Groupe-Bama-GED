<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('status', ['draft', 'review', 'approved', 'archived', 'deleted'])->default('draft')->after('version');
            $table->timestamp('archived_at')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('archived_at');
            $table->string('checksum')->nullable()->after('expires_at'); // SHA256 du fichier
            $table->boolean('is_confidential')->default(false)->after('checksum');
            $table->jsonb('approval_workflow')->nullable()->after('is_confidential'); // Étapes d'approbation
            $table->integer('retention_years')->default(10)->after('approval_workflow'); // Durée de conservation
        });
    }

    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['status', 'archived_at', 'expires_at', 'checksum', 'is_confidential', 'approval_workflow', 'retention_years']);
        });
    }
};

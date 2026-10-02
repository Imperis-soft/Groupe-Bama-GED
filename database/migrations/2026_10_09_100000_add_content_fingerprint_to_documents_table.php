<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Empreinte du contenu (texte, OCR compris) pour détecter les doublons à l'import.
        // Voir App\Support\ContentFingerprint ; remplissage des documents existants : php artisan documents:fingerprint
        Schema::table('documents', function (Blueprint $table) {
            $table->char('content_hash', 64)->nullable()->after('content_text');
            $table->bigInteger('content_simhash')->nullable()->after('content_hash');
            $table->index(['organization_id', 'content_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'content_hash']);
            $table->dropColumn(['content_hash', 'content_simhash']);
        });
    }
};

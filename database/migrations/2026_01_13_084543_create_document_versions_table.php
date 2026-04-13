<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->string('file_path');
            $table->string('checksum')->nullable();
            $table->text('change_description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_versions');
    }
};

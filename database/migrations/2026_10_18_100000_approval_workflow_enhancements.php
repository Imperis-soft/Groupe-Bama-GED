<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Étapes : délai propre à chaque étape (compté à partir de son activation), relances, délégation
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->unsignedSmallInteger('due_days')->nullable()->after('due_at');
            $table->timestamp('activated_at')->nullable()->after('due_days');
            $table->unsignedTinyInteger('reminders_sent')->default(0)->after('activated_at');
            $table->timestamp('last_reminded_at')->nullable()->after('reminders_sent');
            $table->foreignId('delegated_from_id')->nullable()->after('approver_id')->constrained('users')->nullOnDelete();
        });

        // Absences : pendant la période, les validations sont confiées au suppléant
        Schema::table('users', function (Blueprint $table) {
            $table->date('absent_from')->nullable();
            $table->date('absent_until')->nullable();
            $table->foreignId('delegate_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Modèles de circuit réutilisables (ex. « Chef de service → DAF → DG »)
        Schema::create('approval_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // proposé pour cette catégorie
            $table->json('steps'); // [{type: user|department_manager|creator_manager, user_id?, department_id?, due_days?}]
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_templates');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delegate_id');
            $table->dropColumn(['absent_from', 'absent_until']);
        });
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delegated_from_id');
            $table->dropColumn(['due_days', 'activated_at', 'reminders_sent', 'last_reminded_at']);
        });
    }
};

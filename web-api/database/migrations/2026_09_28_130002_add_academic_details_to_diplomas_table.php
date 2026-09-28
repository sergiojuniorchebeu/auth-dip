<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('diplomas', function (Blueprint $table) {
            $table->string('diploma_type')->nullable()->after('program');
            $table->string('specialty')->nullable()->after('diploma_type');
            $table->string('study_level')->nullable()->after('specialty');
            $table->unsignedTinyInteger('study_year')->nullable()->after('study_level');
            $table->decimal('average', 4, 2)->nullable()->after('study_year');
            $table->string('mention')->nullable()->after('average');
            $table->date('issue_date')->nullable()->after('graduation_year');
            $table->string('institution')->default('IAI Cameroun')->after('issue_date');
            $table->string('document_path')->nullable()->after('institution');
            $table->string('transcript_path')->nullable()->after('document_path');
            $table->string('status')->default('active')->after('transcript_path');
        });
    }

    public function down(): void
    {
        Schema::table('diplomas', function (Blueprint $table) {
            $table->dropColumn(['diploma_type', 'specialty', 'study_level', 'study_year', 'average', 'mention', 'issue_date', 'institution', 'document_path', 'transcript_path', 'status']);
        });
    }
};

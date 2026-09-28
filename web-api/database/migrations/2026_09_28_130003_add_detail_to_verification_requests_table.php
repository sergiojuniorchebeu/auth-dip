<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->string('requester_name')->nullable()->after('employer_id');
            $table->string('requester_email')->nullable()->after('requester_name');
            $table->string('requester_phone')->nullable()->after('requester_email');
            $table->date('candidate_birth_date')->nullable()->after('holder_name');
            $table->string('candidate_birth_place')->nullable()->after('candidate_birth_date');
            $table->string('candidate_id_number')->nullable()->after('candidate_birth_place');
            $table->string('candidate_email')->nullable()->after('candidate_id_number');
            $table->string('candidate_phone')->nullable()->after('candidate_email');
            $table->string('employment_position')->nullable()->after('program');
            $table->string('employment_reference')->nullable()->after('employment_position');
            $table->text('verification_purpose')->nullable()->after('employment_reference');
            $table->boolean('candidate_consent')->default(false)->after('verification_purpose');
            $table->string('candidate_document_path')->nullable()->after('candidate_consent');
            $table->string('diploma_document_path')->nullable()->after('candidate_document_path');
            $table->string('transcript_document_path')->nullable()->after('diploma_document_path');
            $table->string('authorization_document_path')->nullable()->after('transcript_document_path');
            $table->text('admin_note')->nullable()->after('decision_note');
        });
    }

    public function down(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->dropColumn(['requester_name', 'requester_email', 'requester_phone', 'candidate_birth_date', 'candidate_birth_place', 'candidate_id_number', 'candidate_email', 'candidate_phone', 'employment_position', 'employment_reference', 'verification_purpose', 'candidate_consent', 'candidate_document_path', 'diploma_document_path', 'transcript_document_path', 'authorization_document_path', 'admin_note']);
        });
    }
};

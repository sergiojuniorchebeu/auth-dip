<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('employer_id')->constrained('users');
            $table->foreignId('diploma_id')->nullable()->constrained()->nullOnDelete();
            $table->string('holder_name');
            $table->string('diploma_number');
            $table->unsignedSmallInteger('graduation_year');
            $table->string('program');
            $table->enum('status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->text('decision_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('verification_requests'); }
};

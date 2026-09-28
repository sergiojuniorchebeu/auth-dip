<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('path');
            $table->string('original_name');
            $table->string('status')->default('pending');
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('company_documents'); }
};

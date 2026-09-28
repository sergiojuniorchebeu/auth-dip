<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('diplomas', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('holder_name');
            $table->string('program');
            $table->unsignedSmallInteger('graduation_year');
            $table->string('qr_token')->unique();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('diplomas'); }
};

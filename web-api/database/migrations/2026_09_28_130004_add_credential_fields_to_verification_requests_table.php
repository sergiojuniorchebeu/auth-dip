<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->string('diploma_type')->nullable()->after('diploma_number');
            $table->string('specialty')->nullable()->after('diploma_type');
            $table->decimal('declared_average', 4, 2)->nullable()->after('specialty');
        });
    }

    public function down(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->dropColumn(['diploma_type', 'specialty', 'declared_average']);
        });
    }
};

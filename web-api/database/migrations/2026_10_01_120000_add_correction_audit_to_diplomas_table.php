<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('diplomas', function (Blueprint $table) {
            $table->text('correction_note')->nullable()->after('status');
            $table->foreignId('corrected_by')->nullable()->after('correction_note')->constrained('users')->nullOnDelete();
            $table->timestamp('corrected_at')->nullable()->after('corrected_by');
        });
    }

    public function down(): void
    {
        Schema::table('diplomas', function (Blueprint $table) {
            $table->dropForeign(['corrected_by']);
            $table->dropColumn(['correction_note', 'corrected_by', 'corrected_at']);
        });
    }
};

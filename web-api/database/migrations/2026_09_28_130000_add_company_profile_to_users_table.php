<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_status')->default('approved')->after('role');
            $table->string('company_legal_name')->nullable()->after('registration_status');
            $table->string('company_registration_number')->nullable()->after('company_legal_name');
            $table->string('company_tax_number')->nullable()->after('company_registration_number');
            $table->string('company_sector')->nullable()->after('company_tax_number');
            $table->string('company_phone')->nullable()->after('company_sector');
            $table->string('company_address')->nullable()->after('company_phone');
            $table->string('company_city')->nullable()->after('company_address');
            $table->string('company_country')->nullable()->after('company_city');
            $table->string('contact_position')->nullable()->after('company_country');
            $table->text('registration_note')->nullable()->after('contact_position');
            $table->timestamp('approved_at')->nullable()->after('registration_note');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['registration_status', 'company_legal_name', 'company_registration_number', 'company_tax_number', 'company_sector', 'company_phone', 'company_address', 'company_city', 'company_country', 'contact_position', 'registration_note', 'approved_at', 'approved_by']);
        });
    }
};

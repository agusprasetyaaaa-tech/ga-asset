<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('license_key')->nullable()->after('serial_number');
            $table->enum('license_type', ['perpetual', 'subscription', 'none'])->default('none')->after('license_key');
            $table->date('license_expiration_date')->nullable()->after('license_type');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['license_key', 'license_type', 'license_expiration_date']);
        });
    }
};

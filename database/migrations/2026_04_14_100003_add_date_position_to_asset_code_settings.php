<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_code_settings', function (Blueprint $table) {
            $table->enum('date_position', ['middle', 'end'])->default('middle')->after('date_format');
        });
    }

    public function down(): void
    {
        Schema::table('asset_code_settings', function (Blueprint $table) {
            $table->dropColumn('date_position');
        });
    }
};

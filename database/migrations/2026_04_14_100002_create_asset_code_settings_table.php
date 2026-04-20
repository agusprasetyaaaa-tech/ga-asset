<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Asset Code Settings table.
     * Stores the configurable template for auto-generating asset codes.
     * 
     * Template components:
     * - prefix: Custom text prefix (e.g., "AST", "INV", "IT-ASSET")
     * - separator: Character between segments (e.g., "-", "/", ".")
     * - date_format: Date segment format (none, Y, Ym, Ymd, ym, ymd)
     * - digit_length: Number of digits for sequential number (3-6)
     * - reset_period: When to reset counter (never, yearly, monthly)
     * - next_number: Current counter value
     * 
     * Example result: AST-202604-00001
     */
    public function up(): void
    {
        Schema::create('asset_code_settings', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->default('AST');
            $table->string('separator')->default('-');
            $table->string('date_format')->default('Ym'); // none, Y, Ym, Ymd, ym, ymd
            $table->integer('digit_length')->default(5);
            $table->enum('reset_period', ['never', 'yearly', 'monthly'])->default('monthly');
            $table->integer('next_number')->default(1);
            $table->string('last_reset_ref')->nullable(); // tracks when last reset happened (e.g., "2026" or "202604")
            $table->timestamps();
        });

        // Insert default setting
        DB::table('asset_code_settings')->insert([
            'prefix' => 'AST',
            'separator' => '-',
            'date_format' => 'Ym',
            'digit_length' => 5,
            'reset_period' => 'monthly',
            'next_number' => 1,
            'last_reset_ref' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_code_settings');
    }
};

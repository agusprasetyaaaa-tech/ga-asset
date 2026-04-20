<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Maintenance Logs table: Records repair, replacement, and inspection history.
     * - type: Category of maintenance (repair, replacement, inspection).
     * - cost: Total cost for the maintenance activity.
     * - technician: Name of the person who performed the work.
     * - completed_at: Date when maintenance was finished (nullable if ongoing).
     */
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['repair', 'replacement', 'inspection'])->default('repair');
            $table->text('description');
            $table->decimal('cost', 15, 2)->default(0);
            $table->string('technician')->nullable();
            $table->date('completed_at')->nullable();
            $table->enum('status', ['proses', 'selesai'])->default('proses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};

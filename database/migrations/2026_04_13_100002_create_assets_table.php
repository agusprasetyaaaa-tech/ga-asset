<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Assets table: Core table storing all inventory items.
     * - barcode_code: Unique identifier for barcode generation (Code128 format).
     * - status: Tracks current condition (available, in_use, maintenance, damaged).
     * - current_holder: Name/identifier of the person currently holding the asset.
     * - location_id: Foreign key to the current physical location.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('serial_number')->unique();
            $table->string('barcode_code')->unique();
            $table->enum('status', ['available', 'in_use', 'maintenance', 'damaged'])->default('available');
            $table->string('current_holder')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

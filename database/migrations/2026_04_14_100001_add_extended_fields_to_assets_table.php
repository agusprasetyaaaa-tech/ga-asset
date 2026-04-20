<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add extended fields for comprehensive asset management.
     * - asset_code: Unique auto-generated code used as barcode identifier
     * - brand, model_type: Hardware identification
     * - specification: Optional technical details
     * - condition: Physical condition (baik, cukup_baik, kurang_baik, rusak)
     * - department: Organizational unit
     * - photo: Path to asset photo
     * - vendor, warranty_date, usage_period: Procurement info
     * - notes: Additional remarks
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('asset_code')->unique()->after('id')->nullable();
            $table->string('brand')->nullable()->after('name');
            $table->string('model_type')->nullable()->after('brand');
            $table->text('specification')->nullable()->after('serial_number');
            $table->enum('condition', ['baik', 'cukup_baik', 'kurang_baik', 'rusak'])->default('baik')->after('specification');
            $table->string('department')->nullable()->after('location_id');
            $table->string('photo')->nullable()->after('description');
            $table->string('vendor')->nullable()->after('purchase_price');
            $table->date('warranty_date')->nullable()->after('vendor');
            $table->string('usage_period')->nullable()->after('warranty_date');
            $table->text('notes')->nullable()->after('usage_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'asset_code', 'brand', 'model_type', 'specification',
                'condition', 'department', 'photo', 'vendor',
                'warranty_date', 'usage_period', 'notes',
            ]);
        });
    }
};

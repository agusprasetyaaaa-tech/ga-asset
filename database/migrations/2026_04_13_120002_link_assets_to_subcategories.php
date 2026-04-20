<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Drop old foreign key and column
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            
            // Add new subcategory link
            $table->foreignId('subcategory_id')->nullable()->after('id')->constrained('subcategories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['subcategory_id']);
            $table->dropColumn('subcategory_id');
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->onDelete('set null');
        });
    }
};

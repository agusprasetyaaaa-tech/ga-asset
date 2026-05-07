<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'borrowed' to status enum
        // Note: For MySQL/MariaDB, we can use MODIFY column
        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('available', 'in_use', 'maintenance', 'damaged', 'borrowed') DEFAULT 'available'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('available', 'in_use', 'maintenance', 'damaged') DEFAULT 'available'");
    }
};

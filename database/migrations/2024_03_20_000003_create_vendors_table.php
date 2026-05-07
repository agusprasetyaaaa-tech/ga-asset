<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $user) {
            $user->id();
            $user->string('name');
            $user->string('contact_person')->nullable();
            $user->string('phone')->nullable();
            $user->string('email')->nullable();
            $user->text('address')->nullable();
            $user->text('description')->nullable();
            $user->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};

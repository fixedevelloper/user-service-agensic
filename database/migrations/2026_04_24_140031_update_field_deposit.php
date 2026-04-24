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
           Schema::table('deposits', function (Blueprint $table) {
           $table->string('provider')->nullable();
            $table->string('provider_token')->nullable();
            $table->json('meta_data')->nullable();
           });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

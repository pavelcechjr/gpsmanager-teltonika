<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Slug značky pro lookup v Simple Icons CDN (volkswagen, ford, skoda, audi…).
            // Null = generický šíp v live mapě.
            $table->string('brand', 32)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }
};

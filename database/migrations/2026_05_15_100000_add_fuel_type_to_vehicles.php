<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Druh paliva / pohonu — pro vizuální označení (zelený badge u hybridů/EV)
 * + budoucí spotřeba (l vs kWh per trip per fuel_type).
 *
 * Enum hodnoty: petrol, diesel, hybrid, phev (plug-in), electric, lpg, cng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('fuel_type', 16)->nullable()->after('brand');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('fuel_type');
        });
    }
};

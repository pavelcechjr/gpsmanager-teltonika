<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            $t->decimal('fuel_tank_l', 5, 1)->nullable()->comment('Fuel tank capacity in liters');
        });

        Schema::table('trips', function (Blueprint $t) {
            $t->decimal('fuel_consumed_l', 6, 2)->nullable()->comment('Liters used during trip (from fuel_level delta × tank size)');
            $t->decimal('fuel_consumption_l_100km', 5, 2)->nullable()->comment('Computed L/100km for this trip');
            $t->smallInteger('fuel_start_pct')->nullable();
            $t->smallInteger('fuel_end_pct')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $t) {
            $t->dropColumn(['fuel_consumed_l', 'fuel_consumption_l_100km', 'fuel_start_pct', 'fuel_end_pct']);
        });
        Schema::table('vehicles', function (Blueprint $t) {
            $t->dropColumn('fuel_tank_l');
        });
    }
};

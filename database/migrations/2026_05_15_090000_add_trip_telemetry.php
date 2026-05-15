<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-trip OBD telemetry aggregates — peak / min / max hodnoty z positions během jízdy.
 *
 * Driving behavior (z OBD2 + GPS):
 *   max_rpm, max_throttle_pct, max_engine_load_pct, max_obd_speed
 *   max_acceleration_ms2 (z GPS speed delta), max_deceleration_ms2 (negative)
 *
 * Engine health:
 *   coolant_temp_min/max, catalyst_temp_max, dtc_change
 *
 * Electrical:
 *   voltage_min/max
 *
 * Engine activity:
 *   engine_run_time_s (z io 42 delta start vs end)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_rpm')->nullable()->after('max_speed');
            $table->unsignedTinyInteger('max_throttle_pct')->nullable()->after('max_rpm');
            $table->unsignedTinyInteger('max_engine_load_pct')->nullable()->after('max_throttle_pct');
            $table->unsignedTinyInteger('max_obd_speed')->nullable()->after('max_engine_load_pct');
            $table->decimal('max_acceleration_ms2', 4, 2)->nullable()->after('max_obd_speed');
            $table->decimal('max_deceleration_ms2', 4, 2)->nullable()->after('max_acceleration_ms2');

            $table->smallInteger('coolant_temp_min')->nullable()->after('max_deceleration_ms2');
            $table->smallInteger('coolant_temp_max')->nullable()->after('coolant_temp_min');
            $table->smallInteger('catalyst_temp_max')->nullable()->after('coolant_temp_max');
            $table->smallInteger('dtc_change')->nullable()->after('catalyst_temp_max');

            $table->decimal('voltage_min', 4, 2)->nullable()->after('dtc_change');
            $table->decimal('voltage_max', 4, 2)->nullable()->after('voltage_min');

            $table->unsignedInteger('engine_run_time_s')->nullable()->after('voltage_max');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'max_rpm', 'max_throttle_pct', 'max_engine_load_pct', 'max_obd_speed',
                'max_acceleration_ms2', 'max_deceleration_ms2',
                'coolant_temp_min', 'coolant_temp_max', 'catalyst_temp_max', 'dtc_change',
                'voltage_min', 'voltage_max',
                'engine_run_time_s',
            ]);
        });
    }
};

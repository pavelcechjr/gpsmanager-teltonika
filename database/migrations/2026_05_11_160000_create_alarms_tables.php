<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alarm_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('type', 40);            // speed_limit, voltage_low, dtc_present, parking_long, device_offline, night_movement, fuel_low, geofence_enter, geofence_exit, hv_battery_low
            $t->foreignId('vehicle_id')->nullable()->constrained()->cascadeOnDelete(); // null = applies to all active vehicles
            $t->json('config')->nullable();    // type-specific (e.g. {limit_kmh: 130})
            $t->string('severity', 10)->default('warn'); // info | warn | critical
            $t->json('notify_emails')->nullable(); // array of emails to notify
            $t->integer('cooldown_min')->default(15); // dedupe — no new event for same rule+vehicle within X min
            $t->boolean('active')->default(true);
            $t->text('note')->nullable();
            $t->timestamps();

            $t->index(['active', 'vehicle_id']);
            $t->index(['type', 'active']);
        });

        Schema::create('alarm_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rule_id')->constrained('alarm_rules')->cascadeOnDelete();
            $t->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $t->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('triggered_at');
            $t->timestamp('resolved_at')->nullable();
            $t->string('severity', 10)->default('warn');
            $t->string('summary', 255);
            $t->json('data')->nullable(); // snapshot: speed value, voltage, location, etc.
            $t->boolean('notified')->default(false);
            $t->timestamps();

            $t->index(['resolved_at', 'triggered_at']);
            $t->index(['vehicle_id', 'triggered_at']);
            $t->index(['rule_id', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarm_events');
        Schema::dropIfExists('alarm_rules');
    }
};

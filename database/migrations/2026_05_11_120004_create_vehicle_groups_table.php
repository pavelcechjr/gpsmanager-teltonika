<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('color', 7)->default('#6366f1');
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('vehicle_group_vehicle', function (Blueprint $t) {
            $t->foreignId('vehicle_group_id')->constrained()->cascadeOnDelete();
            $t->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $t->primary(['vehicle_group_id', 'vehicle_id']);
        });

        Schema::create('device_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('color', 7)->default('#6366f1');
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('device_group_device', function (Blueprint $t) {
            $t->foreignId('device_group_id')->constrained()->cascadeOnDelete();
            $t->foreignId('device_id')->constrained()->cascadeOnDelete();
            $t->primary(['device_group_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_group_device');
        Schema::dropIfExists('device_groups');
        Schema::dropIfExists('vehicle_group_vehicle');
        Schema::dropIfExists('vehicle_groups');
    }
};

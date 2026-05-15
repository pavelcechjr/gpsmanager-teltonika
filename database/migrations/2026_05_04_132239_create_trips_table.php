<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete()->comment('default z vehicle, lze override');
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable()->comment('null = jízda probíhá');
            $table->double('start_lat')->nullable();
            $table->double('start_lng')->nullable();
            $table->string('start_address')->nullable()->comment('cached reverse geocoded ulice + město');
            $table->double('end_lat')->nullable();
            $table->double('end_lng')->nullable();
            $table->string('end_address')->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('max_speed')->nullable()->comment('km/h');
            $table->text('note')->nullable()->comment('manuální poznámka');
            $table->timestamps();
            $table->index(['vehicle_id', 'started_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('trips'); }
};

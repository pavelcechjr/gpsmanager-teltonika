<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete()->comment('null pokud pre-trip');
            $table->timestamp('recorded_at');
            $table->double('latitude');
            $table->double('longitude');
            $table->unsignedSmallInteger('speed')->nullable()->comment('km/h');
            $table->unsignedSmallInteger('heading')->nullable()->comment('0-360 degrees');
            $table->smallInteger('altitude')->nullable()->comment('meters');
            $table->unsignedTinyInteger('satellites')->nullable();
            $table->unsignedTinyInteger('priority')->nullable()->comment('Teltonika 0=low, 1=high, 2=panic');
            $table->json('io_data')->nullable()->comment('všechna IO data z Teltoniky (ignition, voltage, atd.)');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['device_id', 'recorded_at']);
            $table->index(['trip_id', 'recorded_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('positions'); }
};

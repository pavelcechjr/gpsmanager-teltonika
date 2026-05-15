<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('imei', 32)->unique();
            $table->string('phone_number', 32)->nullable();
            $table->string('model', 64)->nullable()->comment('FMB920, FMC650, FMB001, ...');
            $table->json('config')->nullable()->comment('per-device Teltonika settings');
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index('last_seen_at');
        });
    }
    public function down(): void { Schema::dropIfExists('devices'); }
};

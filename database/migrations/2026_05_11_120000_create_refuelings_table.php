<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refuelings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $t->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('fueled_at');
            $t->decimal('liters', 7, 2);
            $t->decimal('price_total', 9, 2);
            $t->integer('mileage_km')->nullable();
            $t->string('fuel_type', 30)->default('Nafta');
            $t->string('station', 120)->nullable();
            $t->text('note')->nullable();
            $t->timestamps();

            $t->index(['vehicle_id', 'fueled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refuelings');
    }
};

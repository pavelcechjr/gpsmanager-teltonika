<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $t->string('type', 50); // servis, stk, pojisteni, olej, pneu, jine
            $t->date('planned_at')->nullable();
            $t->date('performed_at')->nullable();
            $t->integer('mileage_km')->nullable();
            $t->decimal('price', 10, 2)->nullable();
            $t->string('supplier', 120)->nullable();
            $t->text('note')->nullable();
            $t->timestamps();

            $t->index(['vehicle_id', 'planned_at']);
            $t->index(['vehicle_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};

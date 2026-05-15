<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('type', 30)->default('poi'); // poi, garage, client, fuel
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->integer('radius_meters')->default(100); // geofence circle radius
            $t->string('color', 7)->default('#6366f1'); // hex color for map
            $t->text('note')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

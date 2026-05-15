<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('odometer_calibrations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $t->timestamp('applied_at');           // virtual "trip" timestamp (chronology)
            $t->integer('delta_km');                // +/- correction in km
            $t->text('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();

            $t->index(['vehicle_id', 'applied_at']);
        });

        Schema::table('trips', function (Blueprint $t) {
            $t->boolean('is_private')->default(false)->comment('false = služební, true = soukromá');
            $t->integer('odometer_end_km')->nullable()->comment('Estimated odometer at trip end (snapshot)');

            $t->index(['vehicle_id', 'is_private', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $t) {
            $t->dropIndex(['vehicle_id', 'is_private', 'started_at']);
            $t->dropColumn(['is_private', 'odometer_end_km']);
        });
        Schema::dropIfExists('odometer_calibrations');
    }
};

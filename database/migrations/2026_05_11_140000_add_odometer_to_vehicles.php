<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            $t->integer('odometer_km')->nullable()->comment('Last manually entered cluster reading (km)');
            $t->date('odometer_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $t) {
            $t->dropColumn(['odometer_km', 'odometer_updated_at']);
        });
    }
};

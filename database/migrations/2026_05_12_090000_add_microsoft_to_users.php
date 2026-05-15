<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // password nullable — SSO-only users may not have a local hash
        DB::statement('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');

        Schema::table('users', function (Blueprint $t) {
            $t->string('microsoft_oid', 64)->nullable()->unique()->comment('Azure AD Object ID — stable identifier');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('microsoft_oid');
        });
        // Note: re-enforcing NOT NULL on password is risky if SSO users exist; left to manual handling.
    }
};

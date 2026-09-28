<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('member_activities')) {
            return;
        }

        // Voucher claim/redeem/void are not location-bound. Match point_logs.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE member_activities MODIFY location_id BIGINT UNSIGNED NULL');

            return;
        }

        Schema::table('member_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('member_activities')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE member_activities MODIFY location_id BIGINT UNSIGNED NOT NULL');

            return;
        }

        Schema::table('member_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });
    }
};

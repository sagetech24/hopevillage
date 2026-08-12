<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_items', function (Blueprint $table) {
            $table->unsignedInteger('daily_limit_quantity')->nullable()->after('stock');
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dateTime('submitted_at')->nullable()->after('status');
        });

        DB::table('marketplace_orders')
            ->whereNull('submitted_at')
            ->whereIn('status', ['pending_pickup', 'fulfilled', 'cancelled', 'voided'])
            ->update([
                'submitted_at' => DB::raw('COALESCE(fulfilled_at, updated_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });

        Schema::table('marketplace_items', function (Blueprint $table) {
            $table->dropColumn('daily_limit_quantity');
        });
    }
};

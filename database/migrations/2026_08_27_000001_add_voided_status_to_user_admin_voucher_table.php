<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE user_admin_voucher MODIFY COLUMN status ENUM('claimed', 'redeemed', 'voided') NOT NULL DEFAULT 'claimed'");
        }

        Schema::table('user_admin_voucher', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('redeemed_at_merchant_id');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason', 500)->nullable()->after('voided_by');
            $table->unsignedInteger('points_refunded')->default(0)->after('void_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_admin_voucher', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason', 'points_refunded']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('user_admin_voucher')->where('status', 'voided')->update(['status' => 'claimed']);
            DB::statement("ALTER TABLE user_admin_voucher MODIFY COLUMN status ENUM('claimed', 'redeemed') NOT NULL DEFAULT 'claimed'");
        }
    }
};

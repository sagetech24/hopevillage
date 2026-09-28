<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchant_admin_voucher_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_voucher_id')->constrained('admin_vouchers')->restrictOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('voucher_name');
            $table->string('voucher_code');
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->unsignedInteger('redeemed_count');
            $table->decimal('cost_per_voucher', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->json('bank_account');
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['merchant_id', 'admin_voucher_id'], 'mav_invoices_merchant_voucher_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_admin_voucher_invoices');
    }
};

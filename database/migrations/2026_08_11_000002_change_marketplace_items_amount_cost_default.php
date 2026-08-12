<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_items', function (Blueprint $table) {
            $table->decimal('amount_cost', 10, 2)->default(0.20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_items', function (Blueprint $table) {
            $table->decimal('amount_cost', 10, 2)->default(0)->change();
        });
    }
};

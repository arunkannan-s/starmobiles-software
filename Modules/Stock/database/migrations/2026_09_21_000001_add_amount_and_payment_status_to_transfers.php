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
        if (Schema::hasTable('transfers') && !Schema::hasColumn('transfers', 'total_amount')) {
            Schema::table('transfers', function (Blueprint $table) {
                $table->decimal('total_amount', 15, 3)->default(0)->after('status');
            });
        }

        if (Schema::hasTable('transfers') && !Schema::hasColumn('transfers', 'payment_status')) {
            Schema::table('transfers', function (Blueprint $table) {
                $table->string('payment_status', 20)->default('Unpaid')->after('total_amount');
            });
        }

        if (Schema::hasTable('transfer_details') && !Schema::hasColumn('transfer_details', 'purchase_price')) {
            Schema::table('transfer_details', function (Blueprint $table) {
                $table->decimal('purchase_price', 15, 3)->default(0)->after('quantity_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transfers') && Schema::hasColumn('transfers', 'payment_status')) {
            Schema::table('transfers', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });
        }

        if (Schema::hasTable('transfers') && Schema::hasColumn('transfers', 'total_amount')) {
            Schema::table('transfers', function (Blueprint $table) {
                $table->dropColumn('total_amount');
            });
        }

        if (Schema::hasTable('transfer_details') && Schema::hasColumn('transfer_details', 'purchase_price')) {
            Schema::table('transfer_details', function (Blueprint $table) {
                $table->dropColumn('purchase_price');
            });
        }
    }
};

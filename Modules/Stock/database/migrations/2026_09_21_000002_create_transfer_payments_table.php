<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records a payment made by a receiving outlet (to_outlet_id) toward the
     * sending outlet (from_outlet_id) that owes it stock transfer value —
     * mirrors supplier_payments, but between two outlets instead of a supplier.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transfer_payments')) {
            Schema::create('transfer_payments', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no', 55);
                $table->date('date');
                $table->unsignedBigInteger('from_outlet_id'); // the outlet owed money (usually the main/sending outlet)
                $table->unsignedBigInteger('to_outlet_id');   // the outlet making the payment (the receiving/branch outlet)
                $table->decimal('amount', 15, 3)->default(0);
                $table->unsignedBigInteger('payment_method_id');
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable(); // outlet session the payment was recorded from
                $table->unsignedBigInteger('company_id');
                $table->string('del_status', 20)->default('Live');
                $table->timestamps();

                $table->index(['company_id', 'from_outlet_id', 'to_outlet_id']);
                $table->index('del_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_payments');
    }
};

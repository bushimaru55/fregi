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
        Schema::table('payments', function (Blueprint $table) {
            // order_no → orderid (F-REGI標準: ORDERID)
            $table->renameColumn('order_no', 'orderid');
            // fregi_receipt_no → receiptno (F-REGI標準: RECEIPTNO)
            $table->renameColumn('fregi_receipt_no', 'receiptno');
            // fregi_slip_no → slipno (F-REGI標準: SLIPNO)
            $table->renameColumn('fregi_slip_no', 'slipno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('orderid', 'order_no');
            $table->renameColumn('receiptno', 'fregi_receipt_no');
            $table->renameColumn('slipno', 'fregi_slip_no');
        });
    }
};

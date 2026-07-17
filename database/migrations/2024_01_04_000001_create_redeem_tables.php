<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redeem_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('user_id')->constrained()->comment('kasir');
            $table->unsignedInteger('total_ticket_scanned');
            $table->unsignedInteger('total_ticket_used');
            $table->decimal('total_value', 15, 2)->default(0);
            $table->timestamp('redeemed_at');
            $table->timestamps();
        });

        Schema::create('redeem_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('redeem_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->string('item_barcode');
            $table->string('item_name');
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('ticket_used');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->timestamps();
        });

        Schema::create('redeem_ticket_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('redeem_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticket_barcode');
            $table->string('ticket_code_5digit', 5);
            $table->boolean('is_used')->default(false);
            $table->foreignId('user_id')->constrained();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index('ticket_code_5digit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redeem_ticket_scans');
        Schema::dropIfExists('redeem_transaction_details');
        Schema::dropIfExists('redeem_transactions');
    }
};

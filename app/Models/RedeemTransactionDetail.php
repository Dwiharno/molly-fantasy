<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemTransactionDetail extends Model
{
    protected $fillable = [
        'redeem_transaction_id', 'item_id', 'item_barcode', 'item_name',
        'qty', 'ticket_used', 'stock_before', 'stock_after',
    ];

    public function redeemTransaction()
    {
        return $this->belongsTo(RedeemTransaction::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}

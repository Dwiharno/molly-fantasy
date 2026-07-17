<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemTicketScan extends Model
{
    protected $fillable = [
        'redeem_transaction_id', 'ticket_barcode', 'ticket_code_5digit',
        'is_used', 'user_id', 'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'scanned_at' => 'datetime',
        ];
    }

    public function redeemTransaction()
    {
        return $this->belongsTo(RedeemTransaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

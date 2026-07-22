<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemTransaction extends Model
{
    protected $fillable = [
        'store_id', 'transaction_code', 'redeem_type', 'member_phone', 'offline_reference', 'user_id', 'total_ticket_scanned',
        'total_ticket_used', 'total_value', 'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_value' => 'decimal:2',
            'redeemed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function details()
    {
        return $this->hasMany(RedeemTransactionDetail::class);
    }

    public function ticketScans()
    {
        return $this->hasMany(RedeemTicketScan::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemStockMovement extends Model
{
    protected $fillable = [
        'item_id', 'store_id', 'type', 'quantity', 'stock_before', 'stock_after',
        'reference_type', 'reference_id', 'notes', 'user_id',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

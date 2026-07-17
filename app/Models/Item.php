<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    public const ALLOCATIONS = [
        'Claw Machine',
        'Redemption Basic',
        'Redemption Reguler',
        'Redemption Premium',
        'Redemption Elektronik',
    ];

    public const CATEGORIES = [
        'Accesories',
        'Boneka ETG',
        'Boneka Local',
        'Boys Toys',
        'Cosmetic',
        'FnB',
        'Girls Accesories',
        'Girls Toys',
        'Household',
        'Mix Toys',
        'Plush Toys',
        'Toys Kids',
    ];

    public const SUB_CATEGORIES = [
        'Accesories',
        'Beverage',
        'Boneka ETG',
        'Boneka Japan',
        'Boneka Lisensi Besar',
        'Boneka Lisensi/Japan',
        'Boneka Local',
        'Boneka Local Big',
        'Boneka Lokal',
        'Boys Toys',
        'Console',
        'Cosmetic',
        'Daily Needs',
        'Dreamcatcher',
        'Elektronik',
        'FnB',
        'Girls Toys',
        'Houseware',
        'Ice Cream',
        'Mini Baby Japan',
        'Mini Baby Local',
        'Mix Toys',
        'Pin',
        'Plush Toys',
        'Snack',
        'Stationery',
        'Sweet Land',
        'Toys Boys',
        'Toys Girls',
    ];

    protected $fillable = [
        'barcode', 'name', 'category', 'sub_category', 'allocation',
        'selling_price', 'ticket_redeem_qty', 'stock', 'minimum_stock',
        'is_active',
        // Kolom lama (tidak lagi dipakai form, dibiarkan untuk kompatibilitas data lama)
        'store_id', 'category_id', 'sub_category_id', 'brand_id', 'supplier_id',
        'vendor', 'cost_price', 'rack_location', 'image',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(ItemStockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->minimum_stock;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'minimum_stock');
    }
}

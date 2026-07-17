<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleSheetsSyncLog extends Model
{
    protected $fillable = ['sheet_name', 'status', 'message', 'synced_at'];

    protected function casts(): array
    {
        return ['synced_at' => 'datetime'];
    }
}

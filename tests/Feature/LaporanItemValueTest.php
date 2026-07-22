<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\RedeemTransaction;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanItemValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_and_redeem_reports_include_unit_price_and_price_times_quantity(): void
    {
        $store = Store::where('code', 'S040')->firstOrFail();
        $staff = User::create([
            'name' => 'Staff Laporan',
            'email' => 'staff.laporan@example.com',
            'password' => 'password123',
            'role' => User::ROLE_STAFF,
            'store_id' => $store->id,
            'is_active' => true,
        ]);
        $item = Item::create([
            'store_id' => $store->id,
            'barcode' => 'REPORT-VALUE-001',
            'name' => 'Hadiah Laporan',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 2500,
            'ticket_redeem_qty' => 10,
            'stock' => 4,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);
        $transaction = RedeemTransaction::create([
            'store_id' => $store->id,
            'transaction_code' => 'RD-REPORT-VALUE',
            'redeem_type' => 'pos',
            'user_id' => $staff->id,
            'total_ticket_scanned' => 30,
            'total_ticket_used' => 30,
            'total_value' => 7500,
            'redeemed_at' => now(),
        ]);
        $transaction->details()->create([
            'item_id' => $item->id,
            'item_barcode' => $item->barcode,
            'item_name' => $item->name,
            'qty' => 3,
            'ticket_used' => 30,
            'stock_before' => 7,
            'stock_after' => 4,
        ]);

        $this->actingAs($staff)->getJson(route('laporan.data', ['type' => 'stock']))
            ->assertOk()
            ->assertJsonFragment(['unit_price' => 2500, 'item_value' => 10000]);

        $this->getJson(route('laporan.data', ['type' => 'redeem']))
            ->assertOk()
            ->assertJsonFragment(['unit_price' => 2500, 'item_value' => 7500]);
    }
}

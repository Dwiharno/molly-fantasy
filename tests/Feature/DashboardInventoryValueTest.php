<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\RedeemTransaction;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInventoryValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_calculates_inventory_and_redeem_value_from_price_times_quantity(): void
    {
        $store = Store::where('code', 'S040')->firstOrFail();
        $staff = User::create([
            'name' => 'Staff Value',
            'email' => 'staff.value@example.com',
            'password' => 'password123',
            'role' => User::ROLE_STAFF,
            'store_id' => $store->id,
            'is_active' => true,
        ]);

        Item::create([
            'store_id' => $store->id,
            'barcode' => 'VALUE-001',
            'name' => 'Hadiah Value',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 100,
            'ticket_redeem_qty' => 10,
            'stock' => 10,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        RedeemTransaction::create([
            'store_id' => $store->id,
            'transaction_code' => 'RD-VALUE-001',
            'redeem_type' => 'pos',
            'user_id' => $staff->id,
            'total_ticket_scanned' => 100,
            'total_ticket_used' => 100,
            'total_value' => 1000,
            'redeemed_at' => now(),
        ]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Total Value Inventory')
            ->assertSee('Total Value Redeem')
            ->assertSee('Rp 1.000');
    }
}

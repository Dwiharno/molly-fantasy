<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Store;
use App\Models\User;
use App\Services\RedeemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreScopedRedeemTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_barcode_uses_stock_from_logged_in_staff_store(): void
    {
        $storeA = Store::where('code', 'S040')->firstOrFail();
        $storeB = Store::where('code', 'S044')->firstOrFail();
        $staff = User::create([
            'name' => 'Staff Outlet S040',
            'email' => 'staff-s040@example.com',
            'password' => 'password123',
            'role' => User::ROLE_STAFF,
            'store_id' => $storeA->id,
            'is_active' => true,
        ]);

        $itemA = Item::create($this->itemData($storeA->id, 10));
        $itemB = Item::create($this->itemData($storeB->id, 20));

        $this->actingAs($staff);
        $service = app(RedeemService::class);
        $service->addManualTicket(1, 1000);
        $service->scanItem(1, 'STORE-SAME-001', 2);
        $transaction = $service->finishTransaction(1);

        $this->assertSame($storeA->id, $transaction->store_id);
        $this->assertSame(8, $itemA->fresh()->stock);
        $this->assertSame(20, $itemB->fresh()->stock);
    }

    private function itemData(int $storeId, int $stock): array
    {
        return [
            'store_id' => $storeId,
            'barcode' => 'STORE-SAME-001',
            'name' => 'Hadiah Outlet',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 100,
            'ticket_redeem_qty' => 100,
            'stock' => $stock,
            'minimum_stock' => 1,
            'is_active' => true,
        ];
    }
}

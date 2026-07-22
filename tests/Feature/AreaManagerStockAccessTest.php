<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaManagerStockAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_manager_can_view_stock_from_all_stores_but_cannot_change_items(): void
    {
        $storeA = Store::where('code', 'S040')->firstOrFail();
        $storeB = Store::where('code', 'S044')->firstOrFail();
        $manager = User::create([
            'name' => 'Area Manager',
            'email' => 'area.manager@example.com',
            'password' => 'password123',
            'role' => User::ROLE_AREA_MANAGER,
            'store_id' => $storeA->id,
            'is_active' => true,
        ]);

        $itemA = Item::create($this->itemData($storeA->id, 'AREA-S040', 'Stok Deltamas'));
        $itemB = Item::create($this->itemData($storeB->id, 'AREA-S044', 'Stok Living World'));

        $response = $this->actingAs($manager)->getJson(route('items.data'));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Stok Deltamas'])
            ->assertJsonFragment(['name' => 'Stok Living World']);
        $this->assertTrue($manager->can('view', $itemA));
        $this->assertTrue($manager->can('view', $itemB));
        $this->assertFalse($manager->can('create', Item::class));
        $this->assertFalse($manager->can('update', $itemA));
        $this->assertFalse($manager->canWrite());
    }

    private function itemData(int $storeId, string $barcode, string $name): array
    {
        return [
            'store_id' => $storeId,
            'barcode' => $barcode,
            'name' => $name,
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 100,
            'ticket_redeem_qty' => 100,
            'stock' => 10,
            'minimum_stock' => 1,
            'is_active' => true,
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Store;
use App\Models\User;
use App\Services\StockOpnameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockOpnameDirectActualTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_lists_all_store_items_and_requires_direct_actual_before_complete(): void
    {
        $store = Store::where('code', 'S040')->firstOrFail();
        $staff = User::create([
            'name' => 'Staff Opname',
            'email' => 'staff.opname@example.com',
            'password' => 'password123',
            'role' => User::ROLE_STAFF,
            'store_id' => $store->id,
            'is_active' => true,
        ]);
        $first = Item::create($this->itemData($store->id, 'OPNAME-DIRECT-1', 5));
        $second = Item::create($this->itemData($store->id, 'OPNAME-DIRECT-2', 8));

        $this->actingAs($staff);
        $service = app(StockOpnameService::class);
        $opname = $service->startSession();

        $this->assertCount(2, $opname->details);
        $this->assertSame(2, $opname->details()->whereNull('scanned_at')->count());

        try {
            $service->complete($opname);
            $this->fail('Stock opname seharusnya belum bisa diselesaikan.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $service->setActualStocks($opname, [
            $first->id => 3,
            $second->id => 10,
        ]);
        $service->complete($opname);

        $this->assertSame(3, $first->fresh()->stock);
        $this->assertSame(10, $second->fresh()->stock);
        $this->assertSame('completed', $opname->fresh()->status);
    }

    private function itemData(int $storeId, string $barcode, int $stock): array
    {
        return [
            'store_id' => $storeId,
            'barcode' => $barcode,
            'name' => $barcode,
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 1000,
            'ticket_redeem_qty' => 10,
            'stock' => $stock,
            'minimum_stock' => 1,
            'is_active' => true,
        ];
    }
}

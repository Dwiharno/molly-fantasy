<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\RedeemTransaction;
use App\Models\User;
use App\Services\RedeemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class RedeemQuantityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_redeem_quantity_can_be_updated_on_same_item_without_duplicate_rows(): void
    {
        $user = User::create([
            'name' => 'Redeem Staff',
            'email' => 'redeemstaff@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $item = Item::create([
            'barcode' => 'REDEEM-QTY-001',
            'name' => 'Hadiah Uji Qty',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 12000,
            'ticket_redeem_qty' => 2,
            'stock' => 10,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        $service = app(RedeemService::class);
        $service->scanTicket(1, '0005007728001082');
        $service->scanItem(1, $item->barcode, 1);

        $transaction = RedeemTransaction::query()->latest()->firstOrFail();
        $this->assertCount(1, $transaction->details);
        $this->assertSame(1, $transaction->details()->first()->qty);

        $service->scanItem(1, $item->barcode, 3);

        $transaction->refresh();
        $this->assertCount(1, $transaction->details);
        $this->assertSame(4, $transaction->details()->first()->qty);
        $this->assertSame(8, $transaction->details()->first()->ticket_used);
        $this->assertSame(8, $transaction->total_ticket_used);
        $this->assertSame(6, $item->fresh()->stock);
    }

    public function test_finish_transaction_persists_total_ticket_scanned_and_used_for_audit(): void
    {
        $user = User::create([
            'name' => 'Redeem Staff Audit',
            'email' => 'redeemstaffaudit@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'phone' => '081234567892',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $item = Item::create([
            'barcode' => 'REDEEM-AUDIT-001',
            'name' => 'Hadiah Audit',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 5000,
            'ticket_redeem_qty' => 2,
            'stock' => 10,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        $service = app(RedeemService::class);
        $service->scanTicket(1, '0005007728001082');
        $service->scanTicket(1, '0005007728001083');
        $service->scanItem(1, $item->barcode, 2);
        $startedAt = RedeemTransaction::query()->latest()->firstOrFail()->redeemed_at;
        $this->travel(5)->minutes();

        $transaction = $service->finishTransaction(1);

        $this->assertNotNull($transaction);
        $this->assertSame(216, $transaction->total_ticket_scanned);
        $this->assertSame(4, $transaction->total_ticket_used);
        $this->assertGreaterThan(0, $transaction->total_ticket_scanned - $transaction->total_ticket_used);
        $this->assertTrue($transaction->redeemed_at->greaterThan($startedAt));
    }

    public function test_update_item_qty_adjusts_stock_ticket_pool_and_uses_valid_movement_types(): void
    {
        $user = User::create([
            'name' => 'Redeem Staff Update Qty',
            'email' => 'redeemstaffupdate@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'phone' => '081234567894',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $item = Item::create([
            'barcode' => 'REDEEM-UPDATE-001',
            'name' => 'Hadiah Update Qty',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 9000,
            'ticket_redeem_qty' => 2,
            'stock' => 10,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        $service = app(RedeemService::class);
        $service->scanTicket(1, '0005007728001082');
        $service->scanItem(1, $item->barcode, 1);

        $increased = $service->updateItemQty(1, $item->barcode, 3);

        $this->assertSame(3, $increased['detail']->qty);
        $this->assertSame(6, $increased['detail']->ticket_used);
        $this->assertSame(102, $increased['pool']);
        $this->assertSame(7, $item->fresh()->stock);
        $this->assertDatabaseHas('item_stock_movements', [
            'item_id' => $item->id,
            'type' => 'redeem',
            'quantity' => 2,
        ]);

        $decreased = $service->updateItemQty(1, $item->barcode, 2);

        $this->assertSame(2, $decreased['detail']->qty);
        $this->assertSame(4, $decreased['detail']->ticket_used);
        $this->assertSame(104, $decreased['pool']);
        $this->assertSame(8, $item->fresh()->stock);
        $this->assertDatabaseHas('item_stock_movements', [
            'item_id' => $item->id,
            'type' => 'in',
            'quantity' => 1,
        ]);
    }

    public function test_remove_item_from_cart_restores_stock_and_ticket_pool(): void
    {
        $user = User::create([
            'name' => 'Redeem Staff Remove',
            'email' => 'redeemstaffremove@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'phone' => '081234567893',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $item = Item::create([
            'barcode' => 'REDEEM-REMOVE-001',
            'name' => 'Hadiah Remove',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 7000,
            'ticket_redeem_qty' => 2,
            'stock' => 10,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        $service = app(RedeemService::class);
        $service->scanTicket(1, '0005007728001082');
        $service->scanItem(1, $item->barcode, 1);

        $result = $service->removeItemFromCart(1, $item->barcode);

        $this->assertSame(10, $item->fresh()->stock);
        $this->assertSame(108, $result['pool']);
        $this->assertSame(0, $result['total_used']);
        $this->assertSame(0, $result['transaction']->details()->count());
    }

    public function test_reset_clears_entire_redeem_session_and_restores_stock(): void
    {
        $user = User::create([
            'name' => 'Redeem Staff Reset',
            'email' => 'redeemstaffreset@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'phone' => '081234567895',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $item = Item::create([
            'barcode' => 'REDEEM-RESET-001',
            'name' => 'Hadiah Reset',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 7000,
            'ticket_redeem_qty' => 2,
            'stock' => 10,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        $service = app(RedeemService::class);
        $service->scanTicket(1, '0005007728001082');
        $service->scanItem(1, $item->barcode, 2);

        $transaction = RedeemTransaction::query()->firstOrFail();
        $result = $service->resetScannedTickets(1);

        $this->assertSame(0, $result['pool']);
        $this->assertSame(0, $result['total_scanned_value']);
        $this->assertSame(0, $result['total_used']);
        $this->assertSame(10, $item->fresh()->stock);
        $this->assertDatabaseMissing('redeem_transactions', ['id' => $transaction->id]);
        $this->assertDatabaseCount('redeem_transaction_details', 0);
        $this->assertDatabaseCount('redeem_ticket_scans', 0);
        $this->assertCount(0, $service->getState(1)['cart']);
    }
}

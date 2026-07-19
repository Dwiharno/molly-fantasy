<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\RedeemTicketScan;
use App\Models\RedeemTransaction;
use App\Models\User;
use App\Services\RedeemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberOfflineRedeemTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_redeem_uses_phone_and_total_ticket_without_ticket_scan(): void
    {
        $user = $this->staff();
        $item = $this->item('MEMBER-001');
        $this->actingAs($user);

        $service = app(RedeemService::class);
        $service->setMemberBalance('081234567890', 100);
        $service->scanItem(RedeemService::MEMBER_POS, $item->barcode, 2);
        $transaction = $service->finishTransaction(RedeemService::MEMBER_POS);

        $this->assertSame('member', $transaction->redeem_type);
        $this->assertSame('081234567890', $transaction->member_phone);
        $this->assertSame(100, $transaction->total_ticket_scanned);
        $this->assertSame(10, $transaction->total_ticket_used);
        $this->assertDatabaseCount(RedeemTicketScan::class, 0);
    }

    public function test_offline_sync_is_idempotent_and_reduces_stock_once(): void
    {
        $user = $this->staff();
        $item = $this->item('OFFLINE-001');
        $this->actingAs($user);
        $reference = (string) Str::uuid();
        $payload = [
            'reference' => $reference,
            'redeem_type' => 'member',
            'member_phone' => '081298765432',
            'total_tickets' => 100,
            'items' => [['barcode' => $item->barcode, 'qty' => 2]],
        ];

        $service = app(RedeemService::class);
        $first = $service->syncOfflineTransaction($payload);
        $second = $service->syncOfflineTransaction($payload);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(8, $item->fresh()->stock);
        $this->assertDatabaseCount(RedeemTransaction::class, 1);
    }

    private function staff(): User
    {
        return User::create([
            'name' => 'Offline Staff',
            'email' => Str::uuid().'@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STAFF,
            'phone' => '081211111111',
            'is_active' => true,
        ]);
    }

    private function item(string $barcode): Item
    {
        return Item::create([
            'barcode' => $barcode,
            'name' => 'Hadiah '.$barcode,
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 10000,
            'ticket_redeem_qty' => 5,
            'stock' => 10,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);
    }
}

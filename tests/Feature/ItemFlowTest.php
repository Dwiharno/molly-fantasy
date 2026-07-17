<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_item_and_it_persists(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('items.store'), [
            'barcode' => 'ITEM-TEST-001',
            'name' => 'Item Test',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 15000,
            'ticket_redeem_qty' => 2,
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'barcode' => 'ITEM-TEST-001',
            'name' => 'Item Test',
        ]);
    }
}

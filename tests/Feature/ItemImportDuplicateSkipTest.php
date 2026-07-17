<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ItemImportDuplicateSkipTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_duplicate_barcode_and_continues(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin5@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081234567894',
            'is_active' => true,
        ]);

        Item::create([
            'barcode' => 'DUP-001',
            'name' => 'Sudah Ada',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 1000,
            'ticket_redeem_qty' => 1,
            'stock' => 0,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->createWithContent('items-duplicate.csv', "barcode,nama,allocation,kategori,sub_kategori,harga,tiket,qty,status\nDUP-001,Duplicate Item,Claw Machine,Accesories,Accesories,15000,2,5,Aktif\nDUP-002,New Unique Item,Claw Machine,Accesories,Accesories,15000,2,5,Aktif\n");

        $response = $this->actingAs($user)->post(route('items.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'barcode' => 'DUP-002',
            'name' => 'New Unique Item',
        ]);
    }
}

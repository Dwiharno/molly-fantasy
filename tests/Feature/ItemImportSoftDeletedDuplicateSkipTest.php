<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ItemImportSoftDeletedDuplicateSkipTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_soft_deleted_duplicate_barcode_and_continues(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin6@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081234567895',
            'is_active' => true,
        ]);

        $existing = Item::create([
            'barcode' => 'SOFT-DUP-001',
            'name' => 'Sudah Ada Tapi Dihapus',
            'allocation' => Item::ALLOCATIONS[0],
            'category' => Item::CATEGORIES[0],
            'sub_category' => Item::SUB_CATEGORIES[0],
            'selling_price' => 1000,
            'ticket_redeem_qty' => 1,
            'stock' => 0,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);
        $existing->delete();

        $file = UploadedFile::fake()->createWithContent('items-soft-delete.csv', "barcode,nama,allocation,kategori,sub_kategori,harga,tiket,qty,status\nSOFT-DUP-001,Deleted Duplicate Item,Claw Machine,Accesories,Accesories,15000,2,5,Aktif\nSOFT-DUP-002,New Unique Item,Claw Machine,Accesories,Accesories,15000,2,5,Aktif\n");

        $response = $this->actingAs($user)->post(route('items.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'barcode' => 'SOFT-DUP-002',
            'name' => 'New Unique Item',
        ]);
    }
}

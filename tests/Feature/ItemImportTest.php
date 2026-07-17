<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ItemImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_items_from_excel_csv(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081234567891',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->createWithContent('items.csv', "barcode,nama,allocation,kategori,sub_kategori,harga,tiket,qty,status\nITEM-CSV-001,Item CSV,Claw Machine,Accesories,Accesories,15000,2,5,Aktif\n");

        $response = $this->actingAs($user)->post(route('items.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'barcode' => 'ITEM-CSV-001',
            'name' => 'Item CSV',
        ]);
    }
}

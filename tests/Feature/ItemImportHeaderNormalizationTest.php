<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ItemImportHeaderNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_accepts_bom_prefixed_headers_and_spaces(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin4@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081234567893',
            'is_active' => true,
        ]);

        $content = "\xEF\xBB\xBFbarcode, nama , allocation , kategori , sub_kategori , harga , tiket , qty , status\nITEM-BOM-001,Item BOM,Claw Machine,Accesories,Accesories,15000,2,5,Aktif\n";
        $file = UploadedFile::fake()->createWithContent('items-bom.csv', $content);

        $response = $this->actingAs($user)->post(route('items.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'barcode' => 'ITEM-BOM-001',
            'name' => 'Item BOM',
        ]);
    }
}

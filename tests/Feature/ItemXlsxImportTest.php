<?php

namespace Tests\Feature;

use App\Exports\ItemImportTemplateExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ItemXlsxImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_items_from_downloaded_template_xlsx(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin3@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '081234567892',
            'is_active' => true,
        ]);

        $content = Excel::raw(new ItemImportTemplateExport(), \Maatwebsite\Excel\Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'item-template');
        file_put_contents($path, $content);

        $file = new UploadedFile(
            $path,
            'template-import-item.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($user)->post(route('items.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('items', [
            'barcode' => '1234567890123',
            'name' => 'Boneka Beruang Kecil',
        ]);
    }
}

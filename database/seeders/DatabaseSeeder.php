<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder ini HANYA membuat data wajib agar sistem bisa langsung dipakai:
     * - 1 akun Super Admin (wajib ada untuk login pertama kali)
     * - Setting default outlet
     * Tidak ada data dummy/palsu untuk item, kategori, transaksi, dsb.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'superadmin@mollyfantasy.co.id');
        $adminPassword = env('ADMIN_PASSWORD');

        if (app()->environment('production') && blank($adminPassword)) {
            throw new \RuntimeException('ADMIN_PASSWORD wajib diisi pada environment production.');
        }

        $adminPassword ??= 'ChangeMe123!';

        $stores = collect([
            ['code' => 'S040', 'name' => 'Mollyfantasy Aeon Mall Deltamas'],
            ['code' => 'S044', 'name' => 'MollyFantasy Living World'],
            ['code' => 'S050', 'name' => 'Mollyfantasy Cihampelas Walk'],
        ])->mapWithKeys(fn ($data) => [$data['code'] => Store::updateOrCreate(
            ['code' => $data['code']], $data + ['is_active' => true]
        )]);

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make($adminPassword),
                'role' => 'super_admin',
                'store_id' => $stores['S040']->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if (! $admin->store_id) {
            $admin->update(['store_id' => $stores['S040']->id]);
        }

        $defaults = [
            'outlet_name' => 'Molly Fantasy Indonesia',
            'outlet_address' => '',
            'outlet_logo' => '',
            'operational_hours' => '10:00 - 22:00',
            'ticket_barcode_length' => '16',
            'ticket_digit_start_from_end' => '6',
            'ticket_digit_length' => '5',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'type' => 'string']);
        }

        $this->command->info("Akun Super Admin tersedia: {$adminEmail}");
        $this->command->warn('Simpan ADMIN_PASSWORD dengan aman dan ganti password setelah login pertama kali.');
    }
}

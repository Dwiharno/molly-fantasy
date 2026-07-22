<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('super_admin','area_manager','admin','staff','viewer') NOT NULL DEFAULT 'staff'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('role', 'area_manager')->update(['role' => 'viewer']);
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('super_admin','admin','staff','viewer') NOT NULL DEFAULT 'staff'");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Category, Sub Category, dan Allocation disederhanakan menjadi daftar pilihan
     * TETAP (fixed list), bukan lagi data master yang bisa ditambah lewat CRUD.
     * Kolom category_id/sub_category_id/brand_id/supplier_id/vendor/store_id lama
     * TIDAK dihapus (supaya tidak ada data hilang), hanya sudah tidak dipakai lagi
     * oleh form Master Item.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('category')->nullable()->after('barcode');
            $table->string('sub_category')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['category', 'sub_category']);
        });
    }
};

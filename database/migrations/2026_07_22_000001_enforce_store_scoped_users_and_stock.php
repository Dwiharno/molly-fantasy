<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach ([
            ['code' => 'S040', 'name' => 'Mollyfantasy Aeon Mall Deltamas'],
            ['code' => 'S044', 'name' => 'MollyFantasy Living World'],
            ['code' => 'S050', 'name' => 'Mollyfantasy Cihampelas Walk'],
        ] as $store) {
            DB::table('stores')->updateOrInsert(['code' => $store['code']], $store + [
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $defaultStoreId = DB::table('stores')->where('code', 'S040')->value('id');
        DB::table('items')->whereNull('store_id')->update(['store_id' => $defaultStoreId]);
        DB::table('users')->whereNull('store_id')->update(['store_id' => $defaultStoreId]);
        DB::table('redeem_transactions')->whereNull('store_id')->update(['store_id' => $defaultStoreId]);
        DB::table('stock_opnames')->whereNull('store_id')->update(['store_id' => $defaultStoreId]);

        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique('items_barcode_unique');
            $table->unique(['store_id', 'barcode'], 'items_store_barcode_unique');
            $table->index(['store_id', 'is_active'], 'items_store_active_index');
        });

        Schema::table('item_stock_movements', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('item_id')->constrained()->nullOnDelete();
            $table->index(['store_id', 'created_at'], 'stock_movements_store_date_index');
        });

        DB::statement('UPDATE item_stock_movements SET store_id = (SELECT store_id FROM items WHERE items.id = item_stock_movements.item_id) WHERE store_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('item_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_id');
        });
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique('items_store_barcode_unique');
            $table->dropIndex('items_store_active_index');
            $table->unique('barcode');
        });
    }
};

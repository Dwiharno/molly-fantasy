<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redeem_transactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('pos_number')->nullable()->after('redeem_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('redeem_transactions', function (Blueprint $table) {
            $table->dropIndex(['pos_number']);
            $table->dropColumn('pos_number');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redeem_transactions', function (Blueprint $table) {
            $table->string('redeem_type', 20)->default('pos')->after('transaction_code')->index();
            $table->string('member_phone', 25)->nullable()->after('redeem_type')->index();
            $table->uuid('offline_reference')->nullable()->after('member_phone')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('redeem_transactions', function (Blueprint $table) {
            $table->dropIndex(['redeem_type']);
            $table->dropIndex(['member_phone']);
            $table->dropUnique(['offline_reference']);
            $table->dropColumn(['redeem_type', 'member_phone', 'offline_reference']);
        });
    }
};

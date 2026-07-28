<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('sender_number', 20)->nullable()->after('transaction_id');
            $table->string('transaction_reference')->nullable()->unique()->after('sender_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['transaction_reference']);
            $table->dropColumn(['sender_number', 'transaction_reference']);
        });
    }
};

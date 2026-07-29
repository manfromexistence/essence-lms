<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'institution_favicon'],
            [
                'value' => json_encode('images/brand/dhaka-it-institute-favicon.png', JSON_UNESCAPED_SLASHES),
                'group' => 'institution',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // Brand migrations intentionally do not restore the previous client's icon.
    }
};

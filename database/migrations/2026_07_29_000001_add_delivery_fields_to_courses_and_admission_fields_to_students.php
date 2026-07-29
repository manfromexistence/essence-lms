<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('delivery_mode', 20)->default('offline')->after('status')->index();
            $table->text('online_details')->nullable()->after('delivery_mode');
            $table->text('offline_details')->nullable()->after('online_details');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('profession')->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->text('admission_purpose')->nullable();
            $table->string('admission_mode', 20)->default('offline')->index();
            $table->string('admission_status', 20)->default('pending')->index();
            $table->timestamp('applied_at')->nullable();
        });

        $now = now();
        foreach ([
            ['key' => 'institution_name', 'value' => 'Dhaka IT Institute', 'group' => 'institution', 'type' => 'string'],
            ['key' => 'institution_logo', 'value' => 'images/brand/dhaka-it-institute-logo.png', 'group' => 'institution', 'type' => 'string'],
            ['key' => 'institution_address', 'value' => 'House #5 (2nd floor), Road #8, Block-C, Section-10, Mirpur-10, Dhaka-1216. Behind Dhaka WASA.', 'group' => 'institution', 'type' => 'text'],
            ['key' => 'theme_primary_color', 'value' => '#168536', 'group' => 'theme', 'type' => 'string'],
            ['key' => 'theme_secondary_color', 'value' => '#171717', 'group' => 'theme', 'type' => 'string'],
        ] as $setting) {
            $setting['value'] = json_encode($setting['value'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting + ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['delivery_mode']);
            $table->dropColumn(['delivery_mode', 'online_details', 'offline_details']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['admission_mode']);
            $table->dropIndex(['admission_status']);
            $table->dropColumn(['profession', 'marital_status', 'admission_purpose', 'admission_mode', 'admission_status', 'applied_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'social_links')) {
                $table->json('social_links')->nullable()->after('profile_image');
            }
            if (!Schema::hasColumn('teachers', 'bio')) {
                $table->text('bio')->nullable()->after('social_links');
            }
            if (!Schema::hasColumn('teachers', 'designation')) {
                $table->string('designation')->nullable()->after('department');
            }
            if (!Schema::hasColumn('teachers', 'display_order')) {
                $table->integer('display_order')->default(0)->after('status');
            }
            if (!Schema::hasColumn('teachers', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('display_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['social_links', 'bio', 'designation', 'display_order', 'is_featured']);
        });
    }
};

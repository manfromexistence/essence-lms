<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pages')) {
            return;
        }

        $now = now();
        foreach ([
            'services' => [
                'title' => 'Services',
                'meta_title' => 'IT Training and Digital Services | Dhaka IT Institute',
                'meta_description' => 'Professional IT training, freelancing mentorship, website development, digital marketing, domain and hosting services.',
            ],
            'team' => [
                'title' => 'Our Team',
                'meta_title' => 'Our Training Team | Dhaka IT Institute',
                'meta_description' => 'Meet the practical instructors and support team at Dhaka IT Institute.',
            ],
        ] as $slug => $page) {
            DB::table('pages')->updateOrInsert(
                ['slug' => $slug],
                [...$page, 'content' => json_encode([]), 'sections' => json_encode([]), 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pages')) {
            DB::table('pages')->whereIn('slug', ['services', 'team'])->delete();
        }
    }
};

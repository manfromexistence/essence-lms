<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Note: Model events are enabled to allow registration number generation

        if (app()->environment('production')) {
            $this->call([
                RoleSeeder::class,
                PermissionSeeder::class,
                AdminUserSeeder::class,
                CourseSeeder::class,
                PageSeeder::class,
                TeamAndServicesSeeder::class,
                BatchSeeder::class,
                CourseVideoSeeder::class,
            ]);

            return;
        }

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
            CourseSeeder::class,
            BatchSeeder::class,
            StudentManagementSeeder::class,
            TeacherManagementSeeder::class,
            PageSeeder::class,
            TeamAndServicesSeeder::class,
            
            // Comprehensive data seeder - populates all missing data
            ComprehensiveDataSeeder::class,
            
            // Optional seeders (uncomment if needed)
            // DemoDataSeeder::class,
            // ClassCoursesBatchesSeeder::class,
            // InventorySeeder::class,
            // AccountSeeder::class,
            // AdminFeaturesSeeder::class,
        ]);
    }
}

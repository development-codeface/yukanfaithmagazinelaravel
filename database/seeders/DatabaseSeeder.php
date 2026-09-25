<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            UserSeeder::class,
            DairySeeder::class,
            VendorSeeder::class,
            FundSeeder::class,
            AccountSeeder::class,
            ExpenseCategorySeeder::class,
            ExpenseSeeder::class,
            TransactionSeeder::class,
           // InvoiceSeeder::class,
            //InvoiceItemSeeder::class,
            AssetSeeder::class,
            RegionsTableSeeder::class,
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,
            PermissionRoleTableSeeder::class,
            RoleUserTableSeeder::class,
            PlansSeeder::class,
            // Magazine-related seeders
            CategoriesSeeder::class,
            BannersSeeder::class,
            TestArticlesSeeder::class,
            ArticleBlocksSeeder::class,
            ArticleGalleryImagesSeeder::class,
            EventsSeeder::class,
        ]);
    }
}

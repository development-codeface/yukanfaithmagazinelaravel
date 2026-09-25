<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['title' => 'user_access'],
            ['title' => 'user_create'],
            ['title' => 'user_edit'],
            ['title' => 'user_show'],
            ['title' => 'user_delete'],
            ['title' => 'permission_access'],
            ['title' => 'permission_create'],
            ['title' => 'permission_edit'],
            ['title' => 'permission_show'],
            ['title' => 'permission_delete'],
            ['title' => 'role_access'],
            ['title' => 'role_create'],
            ['title' => 'role_edit'],
            ['title' => 'role_show'],
            ['title' => 'role_delete'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}

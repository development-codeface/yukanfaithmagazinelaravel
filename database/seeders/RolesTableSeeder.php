<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['title' => 'SuperAdmin'],
            ['title' => 'Dairy Admin'],
           
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}

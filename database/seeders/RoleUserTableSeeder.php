<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserTableSeeder  extends Seeder
{
    public function run()
    {
         DB::table('role_user')->insert([
            ['role_id' => 1, 'user_id'=>'1'],
        ]);
    }
}

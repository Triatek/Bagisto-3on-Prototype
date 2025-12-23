<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('admins')->delete();
        
        \DB::table('admins')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Example',
                'email' => 'admin@example.com',
                'password' => '$2y$12$.v3Rh865j/3jl7Ao/wSCsOf8lGud1Ck2FWA4kmnXEPX0iVl2o2oi6',
                'api_token' => 'Jw0tqCepteORjSQo9bJBcDf4uDURQL1wzKLOsvbrE5eaEVkZya2U8sXhlq7ZJ3JYRVs4ngZtLw9f4DgQ',
                'status' => 1,
                'role_id' => 1,
                'image' => NULL,
                'remember_token' => NULL,
                'created_at' => '2025-11-24 23:09:37',
                'updated_at' => '2025-11-24 23:11:47',
            ),
        ));
        
        
    }
}
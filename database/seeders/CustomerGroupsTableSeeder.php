<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomerGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customer_groups')->delete();
        
        \DB::table('customer_groups')->insert(array (
            0 => 
            array (
                'id' => 1,
                'code' => 'guest',
                'name' => 'Guest',
                'is_user_defined' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'code' => 'general',
                'name' => 'General',
                'is_user_defined' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'code' => 'wholesale',
                'name' => 'Wholesale',
                'is_user_defined' => 0,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}
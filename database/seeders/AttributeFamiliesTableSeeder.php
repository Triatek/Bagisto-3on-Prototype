<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AttributeFamiliesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('attribute_families')->delete();
        
        \DB::table('attribute_families')->insert(array (
            0 => 
            array (
                'id' => 1,
                'code' => 'default',
                'name' => 'Default',
                'status' => 0,
                'is_user_defined' => 1,
            ),
            1 => 
            array (
                'id' => 3,
                'code' => 'baju',
                'name' => 'Baju',
                'status' => 0,
                'is_user_defined' => 1,
            ),
        ));
        
        
    }
}
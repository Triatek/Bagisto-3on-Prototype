<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('categories')->delete();
        
        \DB::table('categories')->insert(array (
            0 => 
            array (
                'id' => 1,
                'position' => 1,
                'logo_path' => NULL,
                'status' => 1,
                'display_mode' => 'products_and_description',
                '_lft' => 1,
                '_rgt' => 6,
                'parent_id' => NULL,
                'additional' => NULL,
                'banner_path' => NULL,
                'created_at' => '2025-11-24 23:09:33',
                'updated_at' => '2025-11-24 23:09:33',
            ),
        ));
        
        
    }
}
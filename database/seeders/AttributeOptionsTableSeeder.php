<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AttributeOptionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('attribute_options')->delete();
        
        \DB::table('attribute_options')->insert(array (
            0 => 
            array (
                'id' => 1,
                'attribute_id' => 23,
                'admin_name' => 'Red',
                'sort_order' => 0,
                'swatch_value' => '',
            ),
            1 => 
            array (
                'id' => 2,
                'attribute_id' => 23,
                'admin_name' => 'Green',
                'sort_order' => 1,
                'swatch_value' => '',
            ),
            2 => 
            array (
                'id' => 3,
                'attribute_id' => 23,
                'admin_name' => 'Yellow',
                'sort_order' => 2,
                'swatch_value' => '',
            ),
            3 => 
            array (
                'id' => 4,
                'attribute_id' => 23,
                'admin_name' => 'Black',
                'sort_order' => 3,
                'swatch_value' => '',
            ),
            4 => 
            array (
                'id' => 5,
                'attribute_id' => 23,
                'admin_name' => 'White',
                'sort_order' => 4,
                'swatch_value' => '',
            ),
            5 => 
            array (
                'id' => 6,
                'attribute_id' => 24,
                'admin_name' => 'S',
                'sort_order' => 0,
                'swatch_value' => NULL,
            ),
            6 => 
            array (
                'id' => 7,
                'attribute_id' => 24,
                'admin_name' => 'M',
                'sort_order' => 1,
                'swatch_value' => NULL,
            ),
            7 => 
            array (
                'id' => 8,
                'attribute_id' => 24,
                'admin_name' => 'L',
                'sort_order' => 2,
                'swatch_value' => NULL,
            ),
            8 => 
            array (
                'id' => 9,
                'attribute_id' => 24,
                'admin_name' => 'XL',
                'sort_order' => 3,
                'swatch_value' => NULL,
            ),
        ));
        
        
    }
}
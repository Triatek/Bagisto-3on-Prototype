<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('products')->delete();
        
        \DB::table('products')->insert(array (
            0 => 
            array (
                'id' => 52,
                'sku' => 'padel-skirt-01',
                'type' => 'configurable',
                'parent_id' => NULL,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:07:54',
                'updated_at' => '2025-12-03 15:07:54',
            ),
            1 => 
            array (
                'id' => 53,
                'sku' => 'padel-skirt-01-red',
                'type' => 'simple',
                'parent_id' => 52,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:07:54',
                'updated_at' => '2025-12-03 15:19:36',
            ),
            2 => 
            array (
                'id' => 54,
                'sku' => 'padel-skirt-01-whire',
                'type' => 'simple',
                'parent_id' => 52,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:07:54',
                'updated_at' => '2025-12-03 15:20:56',
            ),
            3 => 
            array (
                'id' => 55,
                'sku' => 'padel-skirt-01-red-m',
                'type' => 'simple',
                'parent_id' => 52,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:07:54',
                'updated_at' => '2025-12-03 15:22:57',
            ),
            4 => 
            array (
                'id' => 56,
                'sku' => 'padel-skirt-01-white-m',
                'type' => 'simple',
                'parent_id' => 52,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:07:55',
                'updated_at' => '2025-12-03 15:25:15',
            ),
            5 => 
            array (
                'id' => 57,
                'sku' => 'padel-top-01',
                'type' => 'configurable',
                'parent_id' => NULL,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:28:49',
                'updated_at' => '2025-12-03 15:28:49',
            ),
            6 => 
            array (
                'id' => 58,
                'sku' => 'padel-top-01-red-s',
                'type' => 'simple',
                'parent_id' => 57,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:28:49',
                'updated_at' => '2025-12-03 15:33:01',
            ),
            7 => 
            array (
                'id' => 59,
                'sku' => 'padel-top-01-white-s',
                'type' => 'simple',
                'parent_id' => 57,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:28:49',
                'updated_at' => '2025-12-03 15:34:48',
            ),
            8 => 
            array (
                'id' => 62,
                'sku' => 'padel-hat',
                'type' => 'configurable',
                'parent_id' => NULL,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:48:12',
                'updated_at' => '2025-12-03 15:48:12',
            ),
            9 => 
            array (
                'id' => 63,
                'sku' => 'padel-hat-01-red',
                'type' => 'simple',
                'parent_id' => 62,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:48:12',
                'updated_at' => '2025-12-03 15:49:52',
            ),
            10 => 
            array (
                'id' => 64,
                'sku' => 'padel-hat-01-white',
                'type' => 'simple',
                'parent_id' => 62,
                'attribute_family_id' => 1,
                'additional' => NULL,
                'created_at' => '2025-12-03 15:48:12',
                'updated_at' => '2025-12-03 15:51:13',
            ),
        ));
        
        
    }
}
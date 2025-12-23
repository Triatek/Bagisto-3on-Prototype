<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductInventoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('product_inventories')->delete();
        
        \DB::table('product_inventories')->insert(array (
            0 => 
            array (
                'id' => 15,
                'qty' => 9,
                'product_id' => 53,
                'vendor_id' => 0,
                'inventory_source_id' => 1,
            ),
            1 => 
            array (
                'id' => 16,
                'qty' => 9,
                'product_id' => 54,
                'vendor_id' => 0,
                'inventory_source_id' => 1,
            ),
            2 => 
            array (
                'id' => 17,
                'qty' => 9,
                'product_id' => 55,
                'vendor_id' => 0,
                'inventory_source_id' => 1,
            ),
            3 => 
            array (
                'id' => 18,
                'qty' => 9,
                'product_id' => 56,
                'vendor_id' => 0,
                'inventory_source_id' => 1,
            ),
            4 => 
            array (
                'id' => 19,
                'qty' => 9,
                'product_id' => 58,
                'vendor_id' => 0,
                'inventory_source_id' => 1,
            ),
            5 => 
            array (
                'id' => 20,
                'qty' => 9,
                'product_id' => 59,
                'vendor_id' => 0,
                'inventory_source_id' => 1,
            ),
            6 => 
            array (
                'id' => 21,
                'qty' => 9,
                'product_id' => 63,
                'vendor_id' => 0,
                'inventory_source_id' => 1,
            ),
            7 => 
            array (
                'id' => 22,
                'qty' => 9,
                'product_id' => 64,
                'vendor_id' => 0,
                'inventory_source_id' => 1,
            ),
        ));
        
        
    }
}
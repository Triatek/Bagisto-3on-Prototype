<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventorySourcesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('inventory_sources')->delete();
        
        \DB::table('inventory_sources')->insert(array (
            0 => 
            array (
                'id' => 1,
                'code' => 'default',
                'name' => 'Default',
                'description' => NULL,
                'contact_name' => 'Default',
                'contact_email' => 'warehouse@example.com',
                'contact_number' => '1234567899',
                'contact_fax' => NULL,
                'country' => 'US',
                'state' => 'MI',
                'city' => 'Detroit',
                'street' => '12th Street',
                'postcode' => '48127',
                'priority' => 0,
                'latitude' => NULL,
                'longitude' => NULL,
                'status' => 1,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}
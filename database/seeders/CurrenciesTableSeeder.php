<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CurrenciesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('currencies')->delete();
        
        \DB::table('currencies')->insert(array (
            0 => 
            array (
                'id' => 1,
                'code' => 'USD',
                'name' => 'United States Dollar',
                'symbol' => '$',
                'decimal' => 2,
                'group_separator' => ',',
                'decimal_separator' => '.',
                'currency_position' => NULL,
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'code' => 'IDR',
                'name' => 'Indonesian Rupiah',
                'symbol' => 'Rp',
                'decimal' => 0,
                'group_separator' => '',
                'decimal_separator' => '',
                'currency_position' => 'left_with_space',
                'created_at' => '2025-12-03 15:10:24',
                'updated_at' => '2025-12-03 15:10:24',
            ),
        ));
        
        
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocalesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('locales')->delete();
        
        \DB::table('locales')->insert(array (
            0 => 
            array (
                'id' => 1,
                'code' => 'en',
                'name' => 'English',
                'direction' => 'ltr',
                'logo_path' => 'locales/jhwtImVZ4nDkApJuOT8uTX6LqwPHgejwGvz6xxUx.png',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}
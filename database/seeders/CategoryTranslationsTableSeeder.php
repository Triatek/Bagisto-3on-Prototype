<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategoryTranslationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('category_translations')->delete();
        
        \DB::table('category_translations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'category_id' => 1,
                'name' => 'Root',
                'slug' => 'root',
                'url_path' => '',
                'description' => 'Root Category Description',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'locale_id' => NULL,
                'locale' => 'en',
            ),
        ));
        
        
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChannelsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('channels')->delete();
        
        \DB::table('channels')->insert(array (
            0 => 
            array (
                'id' => 1,
                'code' => 'default',
                'timezone' => NULL,
                'theme' => 'default',
                'hostname' => 'http://localhost',
                'logo' => 'channel/1/lNNoiGkMn5nTNLgZUJzZh43Mdo0POrnDHgaxswC4.png',
                'favicon' => 'channel/1/Bx9Ka4HYgPRxNwSSHEpSKDBy62WlCM7NG2dzfOYQ.png',
                'home_seo' => NULL,
                'is_maintenance_on' => 0,
                'allowed_ips' => '',
                'root_category_id' => 1,
                'default_locale_id' => 1,
                'base_currency_id' => 2,
                'created_at' => '2025-11-24 23:09:35',
                'updated_at' => '2025-12-03 16:32:02',
            ),
        ));
        
        
    }
}
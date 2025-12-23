<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customers')->delete();
        
        \DB::table('customers')->insert(array (
            0 => 
            array (
                'id' => 1,
                'first_name' => 'Sahlhafidzirkhami',
            'last_name' => '(Member)',
                'gender' => NULL,
                'date_of_birth' => NULL,
                'email' => 'sahlhafidzirkhami@gmail.com',
                'phone' => NULL,
                'image' => NULL,
                'status' => 1,
                'password' => '$2y$12$Fs1kOYhfafQsWBZYREQ46.RPOMfD59Nv76AdMKJbVR8OW5ZIrpWX6',
                'api_token' => 'onNPIe5pLfxZsGm3W4BjtMrVa7vpGf3nc0dcKzqaRz5oxOmqOoIc8MBYXhXN6IgQhgTjWtJh8wLAede4',
                'customer_group_id' => 2,
                'channel_id' => 1,
                'subscribed_to_news_letter' => 0,
                'is_verified' => 0,
                'is_suspended' => 0,
                'token' => '433c9625daa2e96d3962b3f478a68a71',
                'remember_token' => NULL,
                'created_at' => '2025-12-23 09:07:11',
                'updated_at' => '2025-12-23 09:07:11',
            ),
            1 => 
            array (
                'id' => 2,
                'first_name' => 'DemoRegister',
            'last_name' => '(Member)',
                'gender' => NULL,
                'date_of_birth' => NULL,
                'email' => 'demoRegister@gmail.com',
                'phone' => NULL,
                'image' => NULL,
                'status' => 1,
                'password' => '$2y$12$nUhnQJ/rPk4Sv.KwNymZLeu6QUSFyuikLE1aKTaIbo2tvTw7qzqdC',
                'api_token' => 'YlTNDhfESGp9fHZloBnyJ4LflE8oGfWSSYyyr9KzyAUpEDlm6LCw5JpzOs4qI1Ar7uwbNe5uUaTn7x4M',
                'customer_group_id' => 2,
                'channel_id' => 1,
                'subscribed_to_news_letter' => 0,
                'is_verified' => 1,
                'is_suspended' => 0,
                'token' => NULL,
                'remember_token' => NULL,
                'created_at' => '2025-12-23 09:12:51',
                'updated_at' => '2025-12-23 09:13:58',
            ),
        ));
        
        
    }
}
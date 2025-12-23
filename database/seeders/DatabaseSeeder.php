<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as BagistoDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
public function run()
    {
        // 1. MATIKAN PENGECEKAN FOREIGN KEY (Biar urutan acak tidak error)
        Schema::disableForeignKeyConstraints();

        // 2. PANGGIL SEMUA SEEDER YANG KITA GENERATE
        // (Pastikan nama class sesuai dengan file yang muncul di folder seeders)
        
        $this->call(ChannelsTableSeeder::class);
        $this->call(LocalesTableSeeder::class);
        $this->call(CurrenciesTableSeeder::class);
        
        $this->call(AdminsTableSeeder::class);
        $this->call(CustomerGroupsTableSeeder::class);
        $this->call(CustomersTableSeeder::class);
        
        // Bagian Katalog (Urutan agak penting, tapi aman karena Disable FK)
        $this->call(AttributeFamiliesTableSeeder::class);
        $this->call(AttributesTableSeeder::class);
        $this->call(AttributeOptionsTableSeeder::class);
        
        $this->call(CategoriesTableSeeder::class);
        $this->call(CategoryTranslationsTableSeeder::class);
        
        $this->call(ProductsTableSeeder::class);
        $this->call(ProductFlatTableSeeder::class); // Tabel ini sering berubah
        $this->call(ProductAttributeValuesTableSeeder::class); // Ini isinya detail produk
        
        $this->call(InventorySourcesTableSeeder::class);
        $this->call(ProductInventoriesTableSeeder::class);

        // 3. NYALAKAN LAGI PENGECEKAN FOREIGN KEY
        Schema::enableForeignKeyConstraints();
    }
}

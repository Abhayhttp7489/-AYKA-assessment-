<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            [
                'sku' => 'SKU-1001',
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse',
                'price' => 19.99,
                'currency' => 'USD',
                'stock' => 50,
                'incoming_stock' => 10,
                'supplier_name' => 'Acme Supplies',
            ],
            [
                'sku' => 'SKU-1002',
                'name' => 'Mechanical Keyboard',
                'description' => 'RGB mechanical keyboard',
                'price' => 79.99,
                'currency' => 'USD',
                'stock' => 20,
                'incoming_stock' => 5,
                'supplier_name' => 'Acme Supplies',
            ],
        ];

        Product::upsert($rows, ['sku']);
    }
}
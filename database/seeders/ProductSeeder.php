<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'product_name' => 'Iced Spanish Latte',
                'category' => 'Coffee',
                'price' => 120.00,
                'quantity' => 42,
                'description' => 'Condensed milk sweetness paired with bold double espresso and rich cold milk.',
            ],
            [
                'product_name' => 'Caramel Macchiato',
                'category' => 'Coffee',
                'price' => 135.00,
                'quantity' => 35,
                'description' => 'Velvety steamed milk marked with espresso and layered caramel drizzle.',
            ],
            [
                'product_name' => 'Cafe Mocha',
                'category' => 'Coffee',
                'price' => 130.00,
                'quantity' => 28,
                'description' => 'Espresso blended with Belgian cocoa and finished with whipped cream.',
            ],
            [
                'product_name' => 'Iced Americano',
                'category' => 'Coffee',
                'price' => 95.00,
                'quantity' => 50,
                'description' => 'Double shot of espresso over cold filtered water and ice.',
            ],
            [
                'product_name' => 'Brown Sugar Milk Tea',
                'category' => 'Milk Tea',
                'price' => 110.00,
                'quantity' => 36,
                'description' => 'Roasted brown sugar syrup, fresh milk, and chewy boba pearls.',
            ],
            [
                'product_name' => 'Wintermelon Milk Tea',
                'category' => 'Milk Tea',
                'price' => 105.00,
                'quantity' => 30,
                'description' => 'House-brewed wintermelon tea with creamy milk and grass jelly.',
            ],
            [
                'product_name' => 'Okinawa Milk Tea',
                'category' => 'Milk Tea',
                'price' => 115.00,
                'quantity' => 25,
                'description' => 'Roasted Okinawa black tea with honeyed milk and tapioca pearls.',
            ],
            [
                'product_name' => 'Matcha Latte',
                'category' => 'Milk Tea',
                'price' => 125.00,
                'quantity' => 4,
                'description' => 'Ceremonial-grade matcha whisked into chilled milk — low stock demo item.',
            ],
            [
                'product_name' => 'Chicken Sandwich',
                'category' => 'Food',
                'price' => 130.00,
                'quantity' => 24,
                'description' => 'Grilled chicken, cheese, and herb mayo folded into a toasted panini.',
            ],
            [
                'product_name' => 'Burger',
                'category' => 'Food',
                'price' => 150.00,
                'quantity' => 18,
                'description' => 'Beef patty, aged cheddar, lettuce, and house sauce on a sesame bun.',
            ],
            [
                'product_name' => 'Chocolate Waffle',
                'category' => 'Dessert',
                'price' => 120.00,
                'quantity' => 20,
                'description' => 'Crisp Belgian waffle with dark chocolate, salted butter, and vanilla cream.',
            ],
            [
                'product_name' => 'Chocolate Cake',
                'category' => 'Dessert',
                'price' => 110.00,
                'quantity' => 16,
                'description' => 'Dense chocolate sponge layered with ganache and a thin cocoa glaze.',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['product_name' => $product['product_name']],
                $product
            );
        }
    }
}

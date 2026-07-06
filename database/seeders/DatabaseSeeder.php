<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Banner;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── ADMIN ──────────────────────────────────────────────
        User::create([
            'name'      => 'Admin',
            'email'     => 'admin@woot.com',
            'password'  => Hash::make('password'),
            'user_type' => 'admin',
        ]);

        // ── USUARIO DE PRUEBA ──────────────────────────────────
        User::create([
            'name'      => 'John Fer',
            'email'     => 'user@woot.com',
            'password'  => Hash::make('password'),
            'user_type' => 'customer',
        ]);

        // ── CATEGORÍAS ─────────────────────────────────────────
        $categoriesData = [
            ['name' => "Women's Fashion",          'slug' => 'womens-fashion',            'icon' => 'cat-womens.jpg',     'order' => 1],
            ['name' => 'Men Clothing & Fashion',   'slug' => 'mens-fashion',              'icon' => 'cat-mens.jpg',       'order' => 2],
            ['name' => 'Electronics',              'slug' => 'electronics',               'icon' => 'cat-electronics.jpg','order' => 3],
            ['name' => 'Sports & outdoor',         'slug' => 'sports-outdoor',            'icon' => 'cat-sports.jpg',     'order' => 4],
            ['name' => 'Jewelry & Watches',        'slug' => 'jewelry-watches',           'icon' => 'cat-jewelry.jpg',    'order' => 5],
            ['name' => 'Beauty, Health & Hair',    'slug' => 'beauty-health-hair',        'icon' => 'cat-beauty.jpg',     'order' => 6],
            ['name' => 'Home decoration & Appliance', 'slug' => 'home-decoration',        'icon' => 'cat-home.jpg',       'order' => 7],
            ['name' => 'Toy',                      'slug' => 'toys',                      'icon' => 'cat-toys.jpg',       'order' => 8],
            ['name' => 'Mobile Phones',            'slug' => 'mobile-phones',             'icon' => 'cat-mobile.jpg',     'order' => 9],
            ['name' => 'Computer & Accessories',   'slug' => 'computer-accessories',      'icon' => 'cat-computer.jpg',   'order' => 10],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $categories[] = Category::create(array_merge($data, [
                'status'  => 1,
                'top'     => 1,
                'banner'  => $data['icon'],
            ]));
        }

        // ── MARCAS ─────────────────────────────────────────────
        $brandsData = [
            ['name' => 'Acer',    'slug' => 'acer',    'logo' => 'brand-acer.jpg'],
            ['name' => 'Adidas',  'slug' => 'adidas',  'logo' => 'brand-adidas.jpg'],
            ['name' => 'Aigner',  'slug' => 'aigner',  'logo' => 'brand-aigner.jpg'],
            ['name' => 'Alosa',   'slug' => 'alosa',   'logo' => 'brand-alosa.jpg'],
            ['name' => 'Apple',   'slug' => 'apple',   'logo' => 'brand-apple.jpg'],
            ['name' => 'Samsung', 'slug' => 'samsung', 'logo' => 'brand-samsung.jpg'],
            ['name' => 'Apato',    'slug' => 'apato',   'logo' => 'brand-nike.jpg'],
            ['name' => 'Sony',    'slug' => 'sony',    'logo' => 'brand-sony.jpg'],
        ];

        $brands = [];
        foreach ($brandsData as $data) {
            $brands[] = Brand::create(array_merge($data, ['status' => 1, 'top' => 1, 'order' => 1]));
        }

        // ── PRODUCTOS DE PRUEBA ────────────────────────────────
        $productsData = [
            [
                'name'        => 'Green Chalcedony Crystal Bracelet',
                'slug'        => 'green-chalcedony-crystal-bracelet',
                'unit_price'  => 103.81,
                'discount'    => 10,
                'category_id' => $categories[4]->id,  // Jewelry
                'thumbnail'   => 'product-bracelet.jpg',
                'featured'    => 1,
                'rating'      => 4.5,
            ],
            [
                'name'        => 'Lenovo Legion Y9000P Gaming Laptop',
                'slug'        => 'lenovo-legion-y9000p-gaming-laptop',
                'unit_price'  => 5108.00,
                'discount'    => 5,
                'category_id' => $categories[2]->id,  // Electronics
                'thumbnail'   => 'product-laptop.jpg',
                'featured'    => 1,
                'rating'      => 4.8,
            ],
            [
                'name'        => 'WIKO T10 Smartphone Android 2GB RAM',
                'slug'        => 'wiko-t10-smartphone',
                'unit_price'  => 98.70,
                'discount'    => 15,
                'category_id' => $categories[8]->id,  // Mobile
                'thumbnail'   => 'product-phone.jpg',
                'featured'    => 1,
                'rating'      => 4.2,
            ],
            [
                'name'        => 'VEVOR Egg Bubble Waffle Maker',
                'slug'        => 'vevor-egg-bubble-waffle-maker',
                'unit_price'  => 69.69,
                'discount'    => 0,
                'category_id' => $categories[6]->id,  // Home
                'thumbnail'   => 'product-waffle.jpg',
                'featured'    => 0,
                'rating'      => 4.0,
            ],
            [
                'name'        => 'COODRONY Thick Warm Parkas Men Winter Jacket',
                'slug'        => 'coodrony-mens-winter-jacket',
                'unit_price'  => 38.21,
                'discount'    => 20,
                'category_id' => $categories[1]->id,  // Men Fashion
                'thumbnail'   => 'product-jacket.jpg',
                'featured'    => 1,
                'rating'      => 4.6,
            ],
            [
                'name'        => 'Casio G-Shock Waterproof Sport Watch',
                'slug'        => 'casio-g-shock-watch',
                'unit_price'  => 85.32,
                'discount'    => 8,
                'category_id' => $categories[4]->id,  // Jewelry
                'thumbnail'   => 'product-watch.jpg',
                'featured'    => 1,
                'rating'      => 4.9,
            ],
            [
                'name'        => 'Huitan Vintage Enamel Flower Dangle Earrings',
                'slug'        => 'huitan-vintage-enamel-earrings',
                'unit_price'  => 10.63,
                'discount'    => 0,
                'category_id' => $categories[4]->id,
                'thumbnail'   => 'product-earrings.jpg',
                'featured'    => 0,
                'rating'      => 4.3,
            ],
            [
                'name'        => 'Yeast Birds Nest Face Skin Care Set 7Pcs',
                'slug'        => 'yeast-birds-nest-skin-care-set',
                'unit_price'  => 63.77,
                'discount'    => 12,
                'category_id' => $categories[5]->id,  // Beauty
                'thumbnail'   => 'product-skincare.jpg',
                'featured'    => 1,
                'rating'      => 4.4,
            ],
        ];

        foreach ($productsData as $data) {
            $product = Product::create(array_merge($data, [
                'brand_id'     => $brands[array_rand($brands)]->id,
                'added_by'     => 1,
                'published'    => 1,
                'approved'     => 1,
                'num_of_sale'  => rand(10, 500),
                'discount_type'=> 'percent',
                'unit'         => 'piece',
                'min_qty'      => 1,
            ]));

            // Crear stock para cada producto
            ProductStock::create([
                'product_id' => $product->id,
                'variant'    => null,
                'price'      => $product->unit_price,
                'qty'        => rand(5, 100),
                'sku'        => strtoupper(substr($product->slug, 0, 8)) . '-001',
            ]);
        }

        // ── BANNERS ────────────────────────────────────────────
        $bannersData = [
            ['title' => 'Fall-Tastic Deals',   'image' => 'banner-1.png', 'type' => 'slider', 'order' => 1],
            ['title' => 'Women Fashion Sale',  'image' => 'banner-2.png', 'type' => 'slider', 'order' => 2],
            ['title' => 'Electronics Deals',   'image' => 'banner-3.png', 'type' => 'slider', 'order' => 3],
            ['title' => 'Promo Women Fashion', 'image' => 'promo-1.jpg',  'type' => 'promo_1','order' => 1],
            ['title' => 'Promo Electronics',   'image' => 'promo-2.jpg',  'type' => 'promo_1','order' => 2],
            ['title' => 'Promo Toys',          'image' => 'promo-3.jpg',  'type' => 'promo_1','order' => 3],
        ];

        foreach ($bannersData as $data) {
            Banner::create(array_merge($data, ['status' => 1, 'link' => '#']));
        }

        $this->command->info('✅ Base de datos inicializada con datos de prueba.');
        $this->command->info('   Admin: admin@woot.com / password');
        $this->command->info('   User:  user@woot.com  / password');
    }
}

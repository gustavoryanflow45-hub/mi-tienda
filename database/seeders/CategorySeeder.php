<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $cats = ['Electrónica', 'Ropa', 'Hogar', 'Deportes', 'Juguetes'];
        foreach ($cats as $i => $name) {
            \App\Models\Category::create([
                'name'   => $name,
                'slug'   => Str::slug($name),
                'status' => 1,
                'order'  => $i + 1,
                'icon'   => '',
                'banner' => '',
            ]);
        }
    }
}
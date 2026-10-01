<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizeStoredImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Requiere la extensión GD.');
        }

        Storage::fake('public');
        config(['images.enabled' => true, 'images.format' => 'webp']);
    }

    /** Guarda en el disco falso una imagen con ruido (que no comprima a nada). */
    protected function putImage(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        mt_srand(crc32($path));

        for ($i = 0; $i < 4000; $i++) {
            $color = imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagefilledrectangle($image, $x = mt_rand(0, $width), $y = mt_rand(0, $height), $x + mt_rand(2, 40), $y + mt_rand(2, 40), $color);
        }

        ob_start();
        str_ends_with($path, '.png') ? imagepng($image) : imagejpeg($image, null, 95);
        Storage::disk('public')->put($path, ob_get_clean());
    }

    protected function makeProduct(string $thumbnail, array $photos): Product
    {
        $seller = User::factory()->create(['user_type' => 'seller']);
        $category = Category::create(['name' => 'Ropa', 'slug' => 'ropa', 'status' => 1]);

        return Product::create([
            'name' => 'Camisa',
            'slug' => 'camisa',
            'category_id' => $category->id,
            'added_by' => $seller->id,
            'unit_price' => 20,
            'thumbnail' => $thumbnail,
            'photos' => $photos,
        ]);
    }

    public function test_pretend_reports_the_savings_without_touching_anything(): void
    {
        $this->putImage('banner-1.png', 2400, 800);
        Banner::create(['image' => 'banner-1.png']);

        $this->artisan('images:optimize', ['--pretend' => true])
            ->expectsOutputToContain('banner-1.png → banner-1.webp')
            ->expectsOutputToContain('Se optimizarían 1 imagen(es)')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('banner-1.png');
        Storage::disk('public')->assertMissing('banner-1.webp');
        $this->assertSame('banner-1.png', Banner::first()->image);
    }

    public function test_it_converts_keeps_the_name_and_repoints_every_column(): void
    {
        $this->putImage('banner-1.png', 2400, 800);
        $this->putImage('products/thumbnails/abc.jpg', 1200, 1200);
        $this->putImage('products/photos/p1.jpg', 2000, 3000);
        $this->putImage('products/photos/p2.jpg', 900, 900);
        Banner::create(['image' => 'banner-1.png']);
        $product = $this->makeProduct('products/thumbnails/abc.jpg', ['products/photos/p1.jpg', 'products/photos/p2.jpg']);

        $this->artisan('images:optimize', ['--force' => true])->assertSuccessful();

        $disk = Storage::disk('public');

        // Mismo nombre, extensión .webp: legacy:restore lo sigue encontrando.
        $this->assertSame('banner-1.webp', Banner::first()->image);
        $this->assertSame('products/thumbnails/abc.webp', $product->fresh()->thumbnail);
        $this->assertSame(['products/photos/p1.webp', 'products/photos/p2.webp'], $product->fresh()->photos);

        $this->assertSame([1600, 533], array_slice(getimagesizefromstring($disk->get('banner-1.webp')), 0, 2));
        $this->assertSame([800, 800], array_slice(getimagesizefromstring($disk->get('products/thumbnails/abc.webp')), 0, 2));
        $this->assertSame([1067, 1600], array_slice(getimagesizefromstring($disk->get('products/photos/p1.webp')), 0, 2));

        foreach (['banner-1.png', 'products/thumbnails/abc.jpg', 'products/photos/p1.jpg', 'products/photos/p2.jpg'] as $original) {
            $disk->assertMissing($original);
        }
    }

    public function test_a_file_shared_by_two_columns_gets_the_more_generous_profile(): void
    {
        // Las categorías heredadas usan la misma imagen para icon y banner;
        // un banner con el mismo archivo pide más resolución que el icono.
        config(['images.profiles.category' => ['max' => 300, 'quality' => 80]]);
        $this->putImage('cat-sports.jpg', 2048, 1536);
        $category = Category::create(['name' => 'Deportes', 'slug' => 'deportes', 'icon' => 'cat-sports.jpg', 'banner' => 'cat-sports.jpg']);
        Banner::create(['image' => 'cat-sports.jpg']);

        $this->artisan('images:optimize', ['--force' => true])->assertSuccessful();

        $category->refresh();
        $this->assertSame('cat-sports.webp', $category->icon);
        $this->assertSame('cat-sports.webp', $category->banner);
        $this->assertSame('cat-sports.webp', Banner::first()->image);
        $this->assertSame(1600, getimagesizefromstring(Storage::disk('public')->get('cat-sports.webp'))[0]);
    }

    public function test_keep_originals_leaves_the_old_files_on_disk(): void
    {
        $this->putImage('promo-1.jpg', 2400, 800);
        Banner::create(['image' => 'promo-1.jpg']);

        $this->artisan('images:optimize', ['--force' => true, '--keep-originals' => true])->assertSuccessful();

        $this->assertSame('promo-1.webp', Banner::first()->image);
        Storage::disk('public')->assertExists('promo-1.jpg');
        Storage::disk('public')->assertExists('promo-1.webp');
    }

    public function test_running_it_twice_changes_nothing_the_second_time(): void
    {
        $this->putImage('banner-1.png', 2400, 800);
        Banner::create(['image' => 'banner-1.png']);

        $this->artisan('images:optimize', ['--force' => true])->assertSuccessful();
        $bytes = Storage::disk('public')->get('banner-1.webp');

        $this->artisan('images:optimize', ['--force' => true])
            ->expectsOutputToContain('Nada que optimizar.')
            ->assertSuccessful();

        $this->assertSame($bytes, Storage::disk('public')->get('banner-1.webp'));
    }

    public function test_it_does_not_overwrite_an_existing_file_with_the_target_name(): void
    {
        $this->putImage('promo-2.png', 2400, 800);
        $this->putImage('promo-2.webp', 10, 10);
        $existing = Storage::disk('public')->get('promo-2.webp');
        Banner::create(['image' => 'promo-2.png']);

        $this->artisan('images:optimize', ['--force' => true])
            ->expectsOutputToContain('ya existe promo-2.webp')
            ->assertSuccessful();

        $this->assertSame('promo-2.png', Banner::first()->image);
        $this->assertSame($existing, Storage::disk('public')->get('promo-2.webp'));
        Storage::disk('public')->assertExists('promo-2.png');
    }

    public function test_missing_files_and_non_images_are_reported_and_left_alone(): void
    {
        Storage::disk('public')->put('wallet/proofs/recibo.pdf', "%PDF-1.4\n");
        Banner::create(['image' => 'no-existe.jpg']);
        $user = User::factory()->create();
        DB::table('wallet_recharges')->insert([
            'user_id' => $user->id, 'amount' => 10, 'network' => 'TRC20', 'transaction_id' => 'tx',
            'payment_proof' => 'wallet/proofs/recibo.pdf', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('images:optimize', ['--force' => true])
            ->expectsOutputToContain('no-existe.jpg')
            ->assertSuccessful();

        $this->assertSame('no-existe.jpg', Banner::first()->image);
        $this->assertSame('wallet/proofs/recibo.pdf', DB::table('wallet_recharges')->value('payment_proof'));
        Storage::disk('public')->assertExists('wallet/proofs/recibo.pdf');
    }

    public function test_it_refuses_to_run_without_the_optimizer(): void
    {
        config(['images.enabled' => false]);

        $this->artisan('images:optimize', ['--force' => true])->assertFailed();
    }
}

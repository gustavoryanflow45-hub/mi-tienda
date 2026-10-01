<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    use BuildsCheckoutData;
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

    /** Dimensiones de una imagen guardada en el disco falso. */
    protected function storedSize(string $path): array
    {
        $info = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$info[0], $info[1], $info['mime']];
    }

    /** Escribe una imagen GD en un archivo temporal y la envuelve como subida. */
    protected function upload(\GdImage $image, string $name, string $format = 'png', int $quality = 90): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'img');
        match ($format) {
            'png' => imagepng($image, $path),
            'webp' => imagewebp($image, $path, $quality),
            default => imagejpeg($image, $path, $quality),
        };

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_product_images_are_resized_and_stored_as_webp(): void
    {
        $seller = User::factory()->create(['user_type' => 'seller', 'email_verified_at' => now()]);
        $this->makeShop($seller, 1);
        $category = Category::create(['name' => 'Ropa', 'slug' => 'ropa', 'status' => 1, 'variant_type' => 'none']);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), [
                'name' => 'Camisa',
                'category_id' => $category->id,
                'unit_price' => 20,
                'unit' => 'pc',
                'thumbnail' => UploadedFile::fake()->image('camisa.jpg', 3000, 2000),
                'photos' => [UploadedFile::fake()->image('detalle.jpg', 2000, 4000)],
                'stocks' => [['price' => 20, 'qty' => 5]],
            ])
            ->assertSessionHasNoErrors();

        $product = Product::where('name', 'Camisa')->sole();

        $this->assertStringStartsWith('products/thumbnails/', $product->thumbnail);
        $this->assertStringEndsWith('.webp', $product->thumbnail);
        $this->assertSame([800, 533, 'image/webp'], $this->storedSize($product->thumbnail));

        $this->assertCount(1, $product->photos);
        $this->assertSame([800, 1600, 'image/webp'], $this->storedSize($product->photos[0]));
    }

    /** Envía el formulario de producto con una miniatura de $kilobytes. */
    protected function postProductWithThumbnailOf(int $kilobytes)
    {
        $seller = User::factory()->create(['user_type' => 'seller', 'email_verified_at' => now()]);
        $this->makeShop($seller, 1);
        $category = Category::firstOrCreate(['slug' => 'ropa'], ['name' => 'Ropa', 'status' => 1, 'variant_type' => 'none']);

        return $this->actingAs($seller)->post(route('seller.products.store'), [
            'name' => 'Camisa',
            'category_id' => $category->id,
            'unit_price' => 20,
            'unit' => 'pc',
            'thumbnail' => UploadedFile::fake()->create('foto-movil.jpg', $kilobytes, 'image/jpeg'),
            'stocks' => [['price' => 20, 'qty' => 5]],
        ]);
    }

    public function test_a_phone_photo_up_to_the_upload_limit_is_accepted(): void
    {
        // Antes el límite era 3 MB y una foto de móvil normal (4–6 MB) se rechazaba.
        $this->assertSame(8192, config('images.max_upload_kb'));

        $this->postProductWithThumbnailOf(6000)->assertSessionHasNoErrors();
        $this->assertSame(1, Product::count());
    }

    public function test_a_file_over_the_upload_limit_is_rejected(): void
    {
        $this->postProductWithThumbnailOf(8193)->assertSessionHasErrors('thumbnail');
        $this->assertSame(0, Product::count());
    }

    public function test_an_image_too_big_for_the_memory_limit_is_stored_as_is(): void
    {
        // Agotar memory_limit sería un fatal imposible de atrapar: el
        // optimizador tiene que verlo venir y guardar el original.
        $file = UploadedFile::fake()->image('enorme.jpg', 3000, 2000); // pide ~54 MB
        $original = file_get_contents($file->getRealPath());
        $previous = ini_get('memory_limit');

        ini_set('memory_limit', (string) (memory_get_usage() + 20 * 1024 * 1024));

        try {
            $path = app(ImageOptimizer::class)->store($file, 'products/photos', 'product_photo');
        } finally {
            ini_set('memory_limit', $previous);
        }

        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame($original, Storage::disk('public')->get($path));
    }

    public function test_transparency_survives_the_conversion(): void
    {
        $image = imagecreatetruecolor(1000, 500);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 0, 0, 127));

        $path = app(ImageOptimizer::class)->store($this->upload($image, 'logo.png'), 'avatars', 'avatar');

        $stored = imagecreatefromstring(Storage::disk('public')->get($path));
        $this->assertSame([400, 200], [imagesx($stored), imagesy($stored)]);
        $this->assertSame(127, imagecolorsforindex($stored, imagecolorat($stored, 10, 10))['alpha']);
    }

    public function test_phone_photos_are_rotated_according_to_exif(): void
    {
        // Un móvil guarda la foto vertical "acostada" (400×200) y la marca con
        // Orientation = 6. Al recodificar el EXIF se pierde, así que el giro
        // tiene que quedar aplicado en los píxeles.
        $image = imagecreatetruecolor(400, 200);
        $file = $this->upload($image, 'foto.jpg', 'jpeg');
        file_put_contents($file->getRealPath(), $this->withExifOrientation(file_get_contents($file->getRealPath()), 6));

        $path = app(ImageOptimizer::class)->store($file, 'products/photos', 'product_photo');

        $this->assertSame([200, 400, 'image/webp'], $this->storedSize($path));
    }

    public function test_an_already_light_image_is_kept_as_is(): void
    {
        $image = imagecreatetruecolor(60, 60);
        imagefilledrectangle($image, 0, 0, 30, 30, imagecolorallocate($image, 200, 30, 30));
        $file = $this->upload($image, 'mini.webp', 'webp', 5);
        $original = file_get_contents($file->getRealPath());

        $path = app(ImageOptimizer::class)->store($file, 'avatars', 'avatar');

        $this->assertSame($original, Storage::disk('public')->get($path));
    }

    public function test_files_that_are_not_images_are_stored_untouched(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('comprobante.pdf', "%PDF-1.4\n%fake\n");

        $path = app(ImageOptimizer::class)->store($pdf, 'wallet/proofs', 'payment_proof');

        $this->assertStringEndsWith('.pdf', $path);
        $this->assertSame("%PDF-1.4\n%fake\n", Storage::disk('public')->get($path));
    }

    public function test_it_can_be_turned_off(): void
    {
        config(['images.enabled' => false]);

        $path = app(ImageOptimizer::class)->store(UploadedFile::fake()->image('grande.jpg', 3000, 2000), 'avatars', 'avatar');

        $this->assertSame([3000, 2000, 'image/jpeg'], $this->storedSize($path));
    }

    public function test_an_unknown_profile_is_a_programming_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(ImageOptimizer::class)->store(UploadedFile::fake()->image('a.jpg'), 'x', 'no-existe');
    }

    /** Inserta un segmento APP1 mínimo con el tag Orientation tras el SOI. */
    protected function withExifOrientation(string $jpeg, int $orientation): string
    {
        $tiff = 'II*'."\0".pack('V', 8)            // cabecera TIFF little-endian, IFD0 en el byte 8
            .pack('v', 1)                           // una entrada
            .pack('vvV', 0x0112, 3, 1).pack('vv', $orientation, 0)
            .pack('V', 0);                          // sin IFD siguiente
        $payload = "Exif\0\0".$tiff;

        return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2);
    }
}

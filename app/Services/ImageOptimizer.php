<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Guarda una imagen subida reducida y recodificada según un perfil de
 * config/images.php. Es el sustituto de `$file->store($dir, 'public')` en
 * todos los puntos de subida: devuelve la misma ruta relativa al disco, así
 * que `asset('storage/'.$path)` y `uploaded_asset()` no cambian.
 *
 * Nunca rompe una subida: si GD no está, el archivo no es JPEG/PNG/WebP
 * (un PDF, un GIF) o algo falla al procesarlo, guarda el original tal cual.
 */
class ImageOptimizer
{
    /** Por encima de esto no se intenta abrir: el bitmap no cabría en memoria. */
    private const MAX_PIXELS = 50_000_000;

    public function store(UploadedFile $file, string $directory, string $profile, string $disk = 'public'): string
    {
        $settings = config("images.profiles.{$profile}");

        if (! is_array($settings)) {
            throw new InvalidArgumentException("Perfil de imagen desconocido: {$profile}");
        }

        if (! $this->available()) {
            return $file->store($directory, $disk);
        }

        try {
            $optimized = $this->optimize($file->getRealPath(), (int) $settings['max'], (int) $settings['quality']);
        } catch (Throwable $e) {
            Log::warning('ImageOptimizer: se guarda el original', ['file' => $file->getClientOriginalName(), 'error' => $e->getMessage()]);
            $optimized = null;
        }

        if ($optimized === null) {
            return $file->store($directory, $disk);
        }

        $path = trim($directory, '/').'/'.Str::random(40).'.'.$optimized['extension'];
        Storage::disk($disk)->put($path, $optimized['bytes']);

        return $path;
    }

    public function available(): bool
    {
        return config('images.enabled', true) && extension_loaded('gd');
    }

    /**
     * @return array{bytes: string, extension: string}|null null = guardar el original
     */
    public function optimize(string $sourcePath, int $max, int $quality): ?array
    {
        $info = @getimagesize($sourcePath);

        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        [$width, $height, $type] = $info;

        if ($width * $height > self::MAX_PIXELS) {
            return null;
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath), // falla en WebP animado → original
        };

        if (! $image instanceof GdImage) {
            return null;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $changed = false;

        // Las fotos de móvil llegan "acostadas" con la rotación en el EXIF.
        // El navegador la aplica al mostrar el original, pero al recodificar
        // el EXIF se pierde: hay que aplicarla sobre los píxeles.
        if ($type === IMAGETYPE_JPEG) {
            $changed = $this->applyExifOrientation($image, $sourcePath) || $changed;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) > $max) {
            $ratio = $max / max($width, $height);
            $image = $this->resize($image, max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)));
            $changed = true;
        }

        [$bytes, $extension] = $this->encode($image, $type, $quality);

        // Una imagen ya pequeña y bien comprimida puede crecer al recodificar.
        // Si no hubo que reducirla ni enderezarla, se queda la original.
        if (! $changed && strlen($bytes) >= filesize($sourcePath)) {
            return null;
        }

        return ['bytes' => $bytes, 'extension' => $extension];
    }

    private function resize(GdImage $image, int $width, int $height): GdImage
    {
        $target = imagecreatetruecolor($width, $height);

        // Conserva la transparencia de PNG/WebP.
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));

        imagecopyresampled($target, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $target;
    }

    /** @return array{0: string, 1: string} bytes y extensión */
    private function encode(GdImage $image, int $sourceType, int $quality): array
    {
        $format = config('images.format', 'webp');

        if ($format === 'webp' && ! function_exists('imagewebp')) {
            $format = $sourceType === IMAGETYPE_JPEG ? 'jpeg' : 'png';
        } elseif ($format === 'jpeg' && $sourceType !== IMAGETYPE_JPEG) {
            // JPEG no tiene canal alfa: un PNG con transparencia quedaría con fondo negro.
            $format = 'png';
        }

        imagesavealpha($image, true);

        ob_start();

        match ($format) {
            'webp' => imagewebp($image, null, $quality),
            'png' => imagepng($image, null, 9),
            default => imagejpeg($image, null, $quality),
        };

        $bytes = (string) ob_get_clean();

        return [$bytes, $format === 'jpeg' ? 'jpg' : $format];
    }

    /** @return bool si hubo que rotar o voltear */
    private function applyExifOrientation(GdImage &$image, string $sourcePath): bool
    {
        if (! function_exists('exif_read_data')) {
            return false;
        }

        $exif = @exif_read_data($sourcePath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if ($orientation <= 1 || $orientation > 8) {
            return false;
        }

        // 2, 4, 5 y 7 son espejadas; 3, 6 y 8 (las habituales) solo rotadas.
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        $angle = match ($orientation) {
            3, 4 => 180,
            6, 7 => -90, // imagerotate gira en sentido antihorario: -90 = horario
            5, 8 => 90,
            default => 0,
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);

            if ($rotated instanceof GdImage) {
                $image = $rotated;
            }
        }

        return true;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pasa por ImageOptimizer las imágenes que se subieron antes de que
 * existiera: las reduce, las recodifica a WebP y repunta la base.
 *
 * Solo toca archivos referenciados desde las columnas de COLUMNS; un
 * archivo huérfano en storage/app/public no se toca.
 *
 * El archivo nuevo conserva el nombre y cambia solo la extensión
 * (cat-sports.jpg → cat-sports.webp). No es estética: legacy:restore
 * repone las filas con la ruta vieja y las repara buscando el mismo nombre
 * con otra extensión (repairImageExtensions), así que una restauración
 * posterior sigue encontrando la imagen.
 */
class OptimizeStoredImages extends Command
{
    protected $signature = 'images:optimize
        {--pretend : Muestra lo que haría y cuánto ahorraría, sin escribir nada}
        {--force : No pedir confirmación}
        {--keep-originals : Conserva los archivos originales en disco}';

    protected $description = 'Reduce y pasa a WebP las imágenes ya subidas, y actualiza sus rutas en la base';

    /**
     * Columna → perfil de config/images.php. Las marcadas como JSON guardan
     * un array de rutas (products.photos).
     */
    private const COLUMNS = [
        'banners.image' => 'banner',
        'brands.logo' => 'brand_logo',
        'categories.icon' => 'category',
        'categories.banner' => 'category',
        'products.thumbnail' => 'product_thumbnail',
        'products.photos' => 'product_photo',
        'shops.id_front_image' => 'id_document',
        'shops.id_back_image' => 'id_document',
        'users.avatar' => 'avatar',
        'wallet_recharges.payment_proof' => 'payment_proof',
    ];

    private const JSON_COLUMNS = ['products.photos'];

    public function handle(ImageOptimizer $optimizer): int
    {
        if (! $optimizer->available()) {
            $this->error('ImageOptimizer no está disponible: activa la extensión GD (extension=gd en php.ini) y comprueba IMAGE_OPTIMIZE.');

            return self::FAILURE;
        }

        $references = $this->collectReferences();

        if ($references === []) {
            $this->info('No hay imágenes referenciadas en la base.');

            return self::SUCCESS;
        }

        $pretend = (bool) $this->option('pretend');

        if ($pretend) {
            $this->comment('Modo --pretend: no se escribe nada.');
        } elseif (! $this->option('force') && ! $this->confirm(
            count($references).' imágenes referenciadas. Se reemplazarán por su versión optimizada'
            .($this->option('keep-originals') ? '' : ' y se borrarán los originales').'. ¿Continuar?'
        )) {
            $this->line('Cancelado.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('public');
        $stats = ['optimized' => 0, 'skipped' => 0, 'missing' => [], 'failed' => 0, 'before' => 0, 'after' => 0];
        $planned = []; // destinos ya asignados en esta pasada (en --pretend aún no están en disco)

        foreach ($references as $path => $profiles) {
            if (! $disk->exists($path)) {
                $stats['missing'][] = $path;

                continue;
            }

            $settings = $this->settingsFor($profiles);
            $absolute = $disk->path($path);
            $before = filesize($absolute);

            // Un WebP que ya cabe en el perfil se deja: recodificarlo en cada
            // pasada solo acumularía pérdida de calidad.
            if ($this->isWebpWithin($absolute, $settings['max'])) {
                $stats['skipped']++;

                continue;
            }

            try {
                $result = $optimizer->optimize($absolute, $settings['max'], $settings['quality']);
            } catch (Throwable $e) {
                $this->warn("  {$path}: no se pudo procesar ({$e->getMessage()})");
                $stats['failed']++;

                continue;
            }

            if ($result === null) {
                $stats['skipped']++;

                continue;
            }

            $target = $this->targetPath($path, $result['extension']);

            if ($target !== $path && ($disk->exists($target) || isset($planned[$target]))) {
                $this->warn("  {$path}: ya existe {$target}, se deja como está");
                $stats['skipped']++;

                continue;
            }

            $planned[$target] = true;
            $after = strlen($result['bytes']);
            $this->line(sprintf('  %s → %s  <fg=gray>%s → %s</>', $path, basename($target), $this->kb($before), $this->kb($after)));

            $stats['before'] += $before;
            $stats['after'] += $after;
            $stats['optimized']++;

            if ($pretend) {
                continue;
            }

            if (! $this->replace($path, $target, $result['bytes'])) {
                $stats['failed']++;
                $stats['optimized']--;
                $stats['before'] -= $before;
                $stats['after'] -= $after;
            }
        }

        $this->report($stats, $pretend);

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Ruta → perfiles que la usan. Un mismo archivo puede colgar de varias
     * columnas (las categorías heredadas usan el mismo para icon y banner).
     *
     * @return array<string, string[]>
     */
    private function collectReferences(): array
    {
        $references = [];

        foreach ($this->columns() as $key => [$table, $column]) {
            foreach (DB::table($table)->whereNotNull($column)->pluck($column) as $value) {
                foreach ($this->pathsIn($key, $value) as $path) {
                    $references[$path][] = self::COLUMNS[$key];
                }
            }
        }

        return array_map('array_unique', $references);
    }

    /** @return array<string, array{0: string, 1: string}> solo las columnas que existen */
    private function columns(): array
    {
        $columns = [];

        foreach (array_keys(self::COLUMNS) as $key) {
            [$table, $column] = explode('.', $key);

            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $columns[$key] = [$table, $column];
            }
        }

        return $columns;
    }

    /** @return string[] */
    private function pathsIn(string $key, mixed $value): array
    {
        $paths = in_array($key, self::JSON_COLUMNS, true)
            ? (array) (is_string($value) ? json_decode($value, true) : $value)
            : [$value];

        // Solo rutas relativas al disco: ni URLs externas ni nada que salga de él.
        return array_values(array_filter($paths, fn ($path) => is_string($path)
            && $path !== ''
            && ! str_contains($path, '://')
            && ! str_contains($path, '..')));
    }

    /** El perfil más generoso entre los que usan el archivo, para no quedarse corto en ninguno. */
    private function settingsFor(array $profiles): array
    {
        $settings = array_map(fn ($profile) => config("images.profiles.{$profile}"), $profiles);
        usort($settings, fn ($a, $b) => [$b['max'], $b['quality']] <=> [$a['max'], $a['quality']]);

        return ['max' => (int) $settings[0]['max'], 'quality' => (int) $settings[0]['quality']];
    }

    private function isWebpWithin(string $absolute, int $max): bool
    {
        $info = @getimagesize($absolute);

        return $info && $info[2] === IMAGETYPE_WEBP && max($info[0], $info[1]) <= $max;
    }

    private function targetPath(string $path, string $extension): string
    {
        $directory = dirname($path);

        return ($directory === '.' ? '' : $directory.'/').pathinfo($path, PATHINFO_FILENAME).'.'.$extension;
    }

    /**
     * Escribe el archivo nuevo, repunta la base y, solo si eso salió bien,
     * borra el original. Si falla la base, el archivo nuevo se retira y el
     * original sigue en su sitio: nunca queda una fila apuntando a la nada.
     */
    private function replace(string $path, string $target, string $bytes): bool
    {
        $disk = Storage::disk('public');

        if ($target === $path) {
            // Mismo nombre (un WebP demasiado grande): basta con sobrescribirlo.
            return $disk->put($path, $bytes);
        }

        $disk->put($target, $bytes);

        try {
            DB::transaction(fn () => $this->repoint($path, $target));
        } catch (Throwable $e) {
            $disk->delete($target);
            $this->warn("  {$path}: no se pudo actualizar la base ({$e->getMessage()}); se conserva el original");

            return false;
        }

        if (! $this->option('keep-originals')) {
            $disk->delete($path);
        }

        return true;
    }

    private function repoint(string $from, string $to): void
    {
        foreach ($this->columns() as $key => [$table, $column]) {
            if (! in_array($key, self::JSON_COLUMNS, true)) {
                DB::table($table)->where($column, $from)->update([$column => $to]);

                continue;
            }

            // JSON: se reescribe el array de cada fila que contenga la ruta.
            DB::table($table)->whereNotNull($column)->where($column, 'like', '%'.basename($from).'%')
                ->get(['id', $column])
                ->each(function ($row) use ($table, $column, $from, $to) {
                    $paths = json_decode($row->$column, true);

                    if (! is_array($paths) || ! in_array($from, $paths, true)) {
                        return;
                    }

                    $paths = array_map(fn ($path) => $path === $from ? $to : $path, $paths);
                    DB::table($table)->where('id', $row->id)->update([$column => json_encode($paths)]);
                });
        }
    }

    private function report(array $stats, bool $pretend): void
    {
        $this->newLine();

        if ($stats['optimized'] > 0) {
            $saved = $stats['before'] - $stats['after'];
            $this->info(sprintf(
                '%s %d imagen(es): %s → %s (−%s, %d%%)',
                $pretend ? 'Se optimizarían' : 'Optimizadas',
                $stats['optimized'],
                $this->kb($stats['before']),
                $this->kb($stats['after']),
                $this->kb($saved),
                $stats['before'] > 0 ? round(100 * $saved / $stats['before']) : 0,
            ));
        } else {
            $this->info('Nada que optimizar.');
        }

        if ($stats['skipped'] > 0) {
            $this->line("Ya optimizadas o sin margen de mejora: {$stats['skipped']}");
        }

        if ($stats['failed'] > 0) {
            $this->warn("Con error (se dejaron como estaban): {$stats['failed']}");
        }

        if ($stats['missing'] !== []) {
            $this->warn('Referenciadas en la base pero ausentes del disco:');

            foreach ($stats['missing'] as $path) {
                $this->line('  '.$path);
            }
        }
    }

    private function kb(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 2).' MB'
            : number_format($bytes / 1024).' KB';
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Copia los datos de la base MySQL heredada (la vieja woot_db de XAMPP) a la
 * conexión por defecto. La migración a PostgreSQL de 2026-07 creó el esquema
 * pero no trasladó las filas, así que el home quedaba sin banners, categorías
 * ni productos. Un `migrate:fresh` reproduce el mismo vacío: este comando lo
 * repara sin tocar la base origen, de la que solo lee.
 */
class RestoreLegacyData extends Command
{
    protected $signature = 'legacy:restore
        {--host=127.0.0.1 : Host de la base heredada}
        {--port=3306 : Puerto de la base heredada}
        {--database=woot_db : Nombre de la base heredada}
        {--username=root : Usuario de la base heredada}
        {--password= : Contraseña de la base heredada}
        {--tables= : Lista separada por comas para limitar las tablas a copiar}
        {--with-notifications : Incluye la tabla notifications (por defecto se omite)}
        {--pretend : Muestra lo que haría sin escribir nada}
        {--force : No pedir confirmación}';

    protected $description = 'Restaura los datos de la MySQL heredada en la base actual (idempotente)';

    /**
     * Orden de dependencias: las tablas referenciadas van antes que las que
     * las referencian, para que ninguna clave foránea quede colgando.
     */
    private const TABLES = [
        'users', 'shops', 'categories', 'brands', 'products', 'product_stocks',
        'banners', 'coupons', 'addresses', 'carts', 'wishlists', 'compare_lists',
        'orders', 'order_details', 'reviews', 'subscribers',
        'wallet_recharges', 'wallet_withdrawals', 'wallet_histories',
    ];

    /** Columnas que apuntan a un archivo dentro de storage/app/public. */
    private const IMAGE_COLUMNS = [
        'banners' => ['image'],
        'categories' => ['icon', 'banner'],
        'brands' => ['logo'],
        'products' => ['thumbnail', 'meta_image'],
        'shops' => ['logo'],
    ];

    private const LEGACY = 'legacy_restore';

    public function handle(): int
    {
        $this->configureLegacyConnection();

        try {
            DB::connection(self::LEGACY)->getPdo();
        } catch (Throwable $e) {
            $this->error('No se pudo conectar a la base heredada: '.$e->getMessage());
            $this->line('¿Está MySQL levantado? En XAMPP se arranca desde el panel de control.');

            return self::FAILURE;
        }

        $tables = $this->tablesToProcess();

        if ($this->option('pretend')) {
            $this->comment('Modo --pretend: no se escribe nada.');
        } elseif (! $this->option('force') && ! $this->confirm(
            'Se copiarán datos desde '.$this->option('database').' a "'.DB::getDefaultConnection().'". ¿Continuar?'
        )) {
            $this->line('Cancelado.');

            return self::SUCCESS;
        }

        foreach ($tables as $table) {
            if ($table === 'users') {
                $this->restoreUsers();
            } else {
                $this->restoreTable($table);
            }
        }

        if (! $this->option('pretend')) {
            $this->repairDoubleEncodedJson();
            $this->repairImageExtensions();
        }

        $this->reportMissingFiles();

        return self::SUCCESS;
    }

    // ── Conexión a la base origen ────────────────────────────────────────

    private function configureLegacyConnection(): void
    {
        config(['database.connections.'.self::LEGACY => [
            'driver' => 'mysql',
            'host' => $this->option('host'),
            'port' => $this->option('port'),
            'database' => $this->option('database'),
            'username' => $this->option('username'),
            'password' => (string) $this->option('password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
        ]]);
    }

    /** @return string[] */
    private function tablesToProcess(): array
    {
        if ($list = $this->option('tables')) {
            return array_values(array_filter(array_map('trim', explode(',', $list))));
        }

        $tables = self::TABLES;

        if ($this->option('with-notifications')) {
            $tables[] = 'notifications';
        }

        return $tables;
    }

    // ── Copia genérica ───────────────────────────────────────────────────

    private function restoreTable(string $table): void
    {
        if (! $this->tableExistsInBoth($table)) {
            return;
        }

        $rows = DB::connection(self::LEGACY)->table($table)->get();

        if ($rows->isEmpty()) {
            return;
        }

        if (($have = DB::table($table)->count()) > 0) {
            $this->line("  <fg=yellow>omitida</> {$table}: ya tiene {$have} fila(s)");

            return;
        }

        $columns = $this->columns($table);
        $payload = $rows->map(fn ($row) => $this->mapRow((array) $row, $columns))->all();

        if ($this->option('pretend')) {
            $this->line("  {$table}: copiaría ".count($payload).' fila(s)');

            return;
        }

        DB::transaction(function () use ($table, $payload) {
            foreach (array_chunk($payload, 200) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        });

        $this->resetSequence($table, $columns);
        $this->info("  {$table}: ".count($payload).' fila(s) copiadas');
    }

    /**
     * Los usuarios necesitan un trato aparte: la cuenta local puede ser más
     * reciente que la heredada (contraseña nueva) pero compartir el email. En
     * ese caso conservamos la fila local —y su contraseña— y solo adoptamos el
     * id y el rol del origen, para que products.added_by y compañía encajen.
     */
    private function restoreUsers(): void
    {
        if (! $this->tableExistsInBoth('users')) {
            return;
        }

        $legacy = DB::connection(self::LEGACY)->table('users')->orderBy('id')->get();

        if ($legacy->isEmpty()) {
            return;
        }

        $columns = $this->columns('users');
        $pretend = $this->option('pretend');

        foreach ($legacy as $row) {
            $local = DB::table('users')->whereRaw('lower(email) = ?', [mb_strtolower($row->email)])->first();

            if (! $local) {
                continue;
            }

            $movesId = (int) $local->id !== (int) $row->id;
            $changesRole = $local->user_type !== $row->user_type;

            if ($movesId && DB::table('users')->where('id', $row->id)->exists()) {
                $this->warn("  users: {$row->email} debería tomar el id {$row->id}, pero está ocupado; se deja en {$local->id}");

                continue;
            }

            if (! $movesId && ! $changesRole) {
                continue;
            }

            $changes = array_filter([
                $movesId ? "id {$local->id} → {$row->id}" : null,
                $changesRole ? "rol {$local->user_type} → {$row->user_type}" : null,
            ]);

            if ($pretend) {
                $this->line("  users: {$row->email} — ".implode(', ', $changes));

                continue;
            }

            if ($movesId) {
                DB::table('users')->where('id', $local->id)->update(['id' => $row->id]);
            }

            if ($changesRole) {
                DB::table('users')->where('id', $row->id)->update(['user_type' => $row->user_type]);
            }

            $this->info("  users: {$row->email} conservado (".implode(', ', $changes).', contraseña local intacta)');
        }

        $inserted = 0;

        foreach ($legacy as $row) {
            $clash = DB::table('users')
                ->where('id', $row->id)
                ->orWhereRaw('lower(email) = ?', [mb_strtolower($row->email)])
                ->exists();

            if ($clash) {
                continue;
            }

            if ($pretend) {
                $inserted++;

                continue;
            }

            DB::table('users')->insert($this->mapRow((array) $row, $columns));
            $inserted++;
        }

        if (! $pretend) {
            $this->resetSequence('users', $columns);
        }

        $this->info('  users: '.$inserted.' fila(s) '.($pretend ? 'a importar' : 'importadas'));
    }

    // ── Reparaciones de datos heredados ──────────────────────────────────

    /**
     * Algunas filas guardaron el JSON codificado dos veces ("\"[...]\""). Con
     * el cast 'array' del modelo eso devuelve un string en vez de un array y
     * la galería del producto no renderiza.
     */
    private function repairDoubleEncodedJson(): void
    {
        if (! $this->hasTable('products')) {
            return;
        }

        foreach (['photos', 'choice_options', 'colors', 'variations'] as $column) {
            if (! in_array($column, $this->columns('products'), true)) {
                continue;
            }

            $fixed = 0;

            foreach (DB::table('products')->select('id', $column)->get() as $row) {
                $raw = $row->$column;

                if (! is_string($raw) || $raw === '') {
                    continue;
                }

                $decoded = json_decode($raw, true);

                // Si al decodificar sale otro string que a su vez es JSON,
                // venía envuelto de más; nos quedamos con la capa interior.
                if (! is_string($decoded) || json_decode($decoded, true) === null) {
                    continue;
                }

                DB::table('products')->where('id', $row->id)->update([$column => $decoded]);
                $fixed++;
            }

            if ($fixed > 0) {
                $this->info("  products.{$column}: {$fixed} fila(s) con JSON doble normalizadas");
            }
        }
    }

    /**
     * Repunta las filas cuya imagen no existe en disco pero sí hay un archivo
     * con el mismo nombre y otra extensión (p. ej. la fila decía promo-2.jpg
     * cuando el archivo real era promo-2.png).
     */
    private function repairImageExtensions(): void
    {
        foreach (self::IMAGE_COLUMNS as $table => $columns) {
            if (! $this->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! in_array($column, $this->columns($table), true)) {
                    continue;
                }

                foreach (DB::table($table)->select('id', $column)->get() as $row) {
                    $path = $row->$column;

                    if (! $path || $this->fileExists($path)) {
                        continue;
                    }

                    if ($match = $this->findByBasename($path)) {
                        DB::table($table)->where('id', $row->id)->update([$column => $match]);
                        $this->info("  {$table}.{$column} #{$row->id}: {$path} → {$match}");
                    }
                }
            }
        }
    }

    private function findByBasename(string $path): ?string
    {
        $directory = trim(dirname($path), '.');
        $base = pathinfo($path, PATHINFO_FILENAME);
        $absolute = storage_path('app/public/'.($directory ? $directory.'/' : ''));

        foreach ((array) glob($absolute.$base.'.*') as $candidate) {
            $found = ($directory ? $directory.'/' : '').basename($candidate);

            if ($found !== $path) {
                return $found;
            }
        }

        return null;
    }

    private function reportMissingFiles(): void
    {
        $missing = [];

        foreach (self::IMAGE_COLUMNS as $table => $columns) {
            if (! $this->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! in_array($column, $this->columns($table), true)) {
                    continue;
                }

                foreach (DB::table($table)->pluck($column) as $path) {
                    if ($path && ! $this->fileExists($path)) {
                        $missing[] = "{$table}.{$column}: {$path}";
                    }
                }
            }
        }

        if ($missing === []) {
            $this->newLine();
            $this->info('Todas las imágenes referenciadas existen en storage/app/public.');

            return;
        }

        $this->newLine();
        $this->warn('Imágenes referenciadas que no están en disco:');

        foreach ($missing as $line) {
            $this->line('  '.$line);
        }
    }

    // ── Utilidades ───────────────────────────────────────────────────────

    private function mapRow(array $row, array $columns): array
    {
        $mapped = array_intersect_key($row, array_flip($columns));

        // PostgreSQL rechaza '' en columnas que MySQL toleraba (fechas, enteros).
        return array_map(fn ($value) => $value === '' ? null : $value, $mapped);
    }

    private function resetSequence(string $table, array $columns): void
    {
        if (! in_array('id', $columns, true) || DB::getDriverName() !== 'pgsql') {
            return;
        }

        $sequence = DB::selectOne('select pg_get_serial_sequence(?, ?) as name', [$table, 'id'])->name;

        if ($sequence) {
            DB::statement('select setval(?, coalesce((select max(id) from "'.$table.'"), 1))', [$sequence]);
        }
    }

    private function tableExistsInBoth(string $table): bool
    {
        if (! $this->hasTable($table)) {
            $this->line("  <fg=gray>saltada</> {$table}: no existe en la base actual");

            return false;
        }

        if (! DB::connection(self::LEGACY)->getSchemaBuilder()->hasTable($table)) {
            $this->line("  <fg=gray>saltada</> {$table}: no existe en la base heredada");

            return false;
        }

        return true;
    }

    private function hasTable(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    /** @return string[] */
    private function columns(string $table): array
    {
        return DB::getSchemaBuilder()->getColumnListing($table);
    }

    private function fileExists(string $path): bool
    {
        return is_file(storage_path('app/public/'.$path));
    }
}

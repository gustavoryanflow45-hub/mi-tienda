<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\ProductStock;
use App\Models\SellerSettlement;
use App\Models\User;
use App\Models\WalletRecharge;
use App\Models\WalletWithdrawal;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Support\ApiAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Yaml\Yaml;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * docs/api/openapi.yaml es la especificación de /api/v1 para las apps. Este
 * test impide que se desvíe del código: cada ruta está documentada y nada
 * más; la seguridad y las habilidades coinciden con el middleware de la
 * ruta; todos los $ref resuelven; y respuestas reales de los endpoints
 * principales cumplen su esquema (un validador mínimo de JSON Schema: type,
 * required, properties, items, enum, const, allOf, oneOf, $ref).
 */
class OpenApiSpecTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    private const SPEC = 'docs/api/openapi.yaml';

    private static ?array $spec = null;

    protected function spec(): array
    {
        return self::$spec ??= Yaml::parseFile(base_path(self::SPEC));
    }

    /** @return array<string, Route> "GET /products/{product}" => ruta */
    protected function apiOperations(): array
    {
        $operations = [];

        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $operations[$method.' /'.substr($route->uri(), strlen('api/v1/'))] = $route;
            }
        }

        return $operations;
    }

    /** @return array<string, array> "GET /products/{product}" => operación */
    protected function specOperations(): array
    {
        $operations = [];

        foreach ($this->spec()['paths'] as $path => $item) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (isset($item[$method])) {
                    $operations[strtoupper($method).' '.$path] = $item[$method];
                }
            }
        }

        return $operations;
    }

    // ── La especificación frente a las rutas ────────────────────

    public function test_the_spec_is_openapi_3_1_with_unique_operation_ids(): void
    {
        $this->assertSame('3.1.0', $this->spec()['openapi']);

        $ids = array_column($this->specOperations(), 'operationId');
        $this->assertCount(count($this->specOperations()), $ids, 'Toda operación necesita operationId');
        $this->assertSame($ids, array_unique($ids), 'operationId repetido');
    }

    public function test_every_api_route_is_documented_and_nothing_else(): void
    {
        $routes = array_keys($this->apiOperations());
        $documented = array_keys($this->specOperations());

        $this->assertSame([], array_values(array_diff($routes, $documented)), 'Rutas sin documentar en '.self::SPEC);
        $this->assertSame([], array_values(array_diff($documented, $routes)), 'Operaciones documentadas que no existen');
    }

    public function test_security_and_abilities_match_the_route_middleware(): void
    {
        $spec = $this->specOperations();

        foreach ($this->apiOperations() as $key => $route) {
            $middleware = $route->gatherMiddleware();
            $operation = $spec[$key];

            $public = ! in_array('auth:sanctum', $middleware, true);
            $this->assertSame($public, ($operation['security'] ?? null) === [], "{$key}: security no coincide con auth:sanctum");

            $abilities = collect($middleware)
                ->filter(fn ($m) => is_string($m) && str_starts_with($m, 'abilities:'))
                ->flatMap(fn ($m) => explode(',', substr($m, strlen('abilities:'))))
                ->sort()->values()->all();
            $documented = collect($operation['x-abilities'] ?? [])->sort()->values()->all();

            $this->assertSame($abilities, $documented, "{$key}: x-abilities no coincide con la ruta");
        }
    }

    public function test_every_ref_resolves(): void
    {
        $spec = $this->spec();
        $refs = 0;

        array_walk_recursive($spec, function ($value, $key) use (&$refs) {
            if ($key === '$ref') {
                $refs++;
                $this->assertNotNull($this->resolve($value), "{$value} no existe");
            }
        });

        $this->assertGreaterThan(0, $refs);
    }

    // ── Respuestas reales frente a los esquemas ─────────────────

    public function test_catalog_and_auth_responses_match_the_spec(): void
    {
        $seller = $this->makeSeller();
        $category = Category::create(['name' => 'Calzado', 'slug' => 'calzado', 'status' => 1, 'variant_type' => 'footwear']);
        $product = $this->makeProduct($seller, 50);
        $product->forceFill(['category_id' => $category->id, 'unit' => 'par'])->save();
        $size = config('variants.types.footwear.sizes')[0];
        ProductStock::create(['product_id' => $product->id, 'size' => $size, 'color' => 'negro', 'price' => 50, 'qty' => 3]);

        $this->check($this->getJson('/api/v1/home'), 'GET', '/home');
        $this->check($this->getJson('/api/v1/products?per_page=5'), 'GET', '/products');
        $this->check($this->getJson("/api/v1/products/{$product->slug}"), 'GET', '/products/{product}');
        $this->check($this->getJson('/api/v1/products/no-existe'), 'GET', '/products/{product}', 404);
        $this->check($this->getJson('/api/v1/products?sort=raro'), 'GET', '/products', 422);
        $this->check($this->getJson('/api/v1/categories'), 'GET', '/categories');
        $this->check($this->getJson('/api/v1/categories/calzado'), 'GET', '/categories/{slug}');
        $this->check($this->getJson('/api/v1/brands'), 'GET', '/brands');

        $user = $this->makeCustomer();
        $user->forceFill(['password' => Hash::make('Clave1234')])->save();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email, 'password' => 'Clave1234', 'device_id' => 'spec-device', 'device_name' => 'Spec',
        ]);
        $this->check($login, 'POST', '/auth/login');
        $this->check($this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'mala', 'device_id' => 'x', 'device_name' => 'x']), 'POST', '/auth/login', 401);

        $this->withHeader('Authorization', 'Bearer '.$login->json('data.token'));
        $this->check($this->getJson('/api/v1/auth/me'), 'GET', '/auth/me');
        $this->check($this->getJson('/api/v1/auth/devices'), 'GET', '/auth/devices');
        $this->check($this->getJson('/api/v1/profile'), 'GET', '/profile');

        $this->app['auth']->forgetGuards();
        $this->check($this->flushHeaders()->getJson('/api/v1/cart'), 'GET', '/cart', 401);
    }

    public function test_cart_checkout_and_order_responses_match_the_spec(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, 40);
        ProductStock::create(['product_id' => $product->id, 'price' => 40, 'qty' => 5]);
        $buyer = $this->makeCustomer();
        $this->bearer($buyer);

        $this->check($this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]), 'POST', '/cart/items', 201);
        $this->check($this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 99]), 'POST', '/cart/items', 422);
        $this->check($this->getJson('/api/v1/cart'), 'GET', '/cart');
        $this->check($this->getJson('/api/v1/checkout'), 'GET', '/checkout');

        $order = $this->postJson('/api/v1/checkout/orders', [
            'full_name' => 'Ana', 'phone' => '0999', 'email' => 'ana@correo.com', 'address' => 'Calle 1', 'city' => 'Quito',
        ]);
        $this->check($order, 'POST', '/checkout/orders', 201);
        $id = $order->json('data.id');

        $this->check($this->getJson("/api/v1/checkout/orders/{$id}"), 'GET', '/checkout/orders/{order}');
        $this->check($this->getJson('/api/v1/orders'), 'GET', '/orders');
        $this->check($this->getJson("/api/v1/orders/{$id}"), 'GET', '/orders/{order}');
        $this->check($this->getJson('/api/v1/orders/999999'), 'GET', '/orders/{order}', 404);
    }

    public function test_seller_responses_match_the_spec(): void
    {
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, 30);
        $product->forceFill(['unit' => 'u'])->save();
        ProductStock::create(['product_id' => $product->id, 'color' => 'negro', 'price' => 30, 'qty' => 2]);

        $order = $this->makePendingOrder($this->makeCustomer(), $seller, 30);
        $order->forceFill(['payment_status' => 'paid'])->save();

        $this->bearer($seller);

        $this->check($this->getJson('/api/v1/seller/dashboard'), 'GET', '/seller/dashboard');
        $this->check($this->getJson('/api/v1/seller/products/options'), 'GET', '/seller/products/options');
        $this->check($this->getJson('/api/v1/seller/products'), 'GET', '/seller/products');
        $this->check($this->getJson("/api/v1/seller/products/{$product->id}"), 'GET', '/seller/products/{product}');
        $this->check($this->putJson("/api/v1/seller/products/{$product->id}", ['name' => 'Otro nombre']), 'PUT', '/seller/products/{product}');
        $this->check($this->getJson('/api/v1/seller/orders'), 'GET', '/seller/orders');
        $this->check($this->postJson("/api/v1/seller/orders/{$order->id}/confirm"), 'POST', '/seller/orders/{order}/confirm');

        $customer = $this->makeCustomer();
        $this->bearer($customer);
        $this->check($this->getJson('/api/v1/seller/dashboard'), 'GET', '/seller/dashboard', 403);
    }

    public function test_wallet_and_notification_responses_match_the_spec(): void
    {
        $seller = $this->makeSeller();
        $seller->forceFill(['balance' => 50])->save();
        SellerSettlement::create([
            'seller_id' => $seller->id, 'total_sales' => 40, 'commission_rate' => 0.25,
            'commission' => 10, 'net_amount' => 30, 'lines_count' => 1, 'settled_at' => now(),
        ]);
        $seller->notify(new OrderStatusUpdatedNotification($this->makePendingOrder($seller, $this->makeSeller()), 'confirmed'));

        $this->bearer($seller);

        $this->check($this->getJson('/api/v1/wallet'), 'GET', '/wallet');
        $this->check($this->withHeader('Idempotency-Key', 'spec-recharge-1')->post('/api/v1/wallet/recharges', [
            'amount' => 10, 'network' => 'TRC20', 'transaction_id' => 'tx',
            'payment_proof' => UploadedFile::fake()->create('comprobante.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json']), 'POST', '/wallet/recharges', 201);
        $this->check($this->getJson('/api/v1/wallet/recharges'), 'GET', '/wallet/recharges');
        $bank = ['full_name' => 'Ana Pérez', 'bank_name' => 'Banco', 'account_number' => '12345'];
        $this->check($this->withHeader('Idempotency-Key', 'spec-withdraw-1')->postJson('/api/v1/wallet/withdrawals', ['amount' => 20] + $bank), 'POST', '/wallet/withdrawals', 201);
        $this->check($this->withHeader('Idempotency-Key', 'spec-withdraw-2')->postJson('/api/v1/wallet/withdrawals', ['amount' => 999] + $bank), 'POST', '/wallet/withdrawals', 422);
        $this->check($this->withHeader('Idempotency-Key', 'x')->postJson('/api/v1/wallet/withdrawals', ['amount' => 1] + $bank), 'POST', '/wallet/withdrawals', 400);
        $this->check($this->getJson('/api/v1/wallet/withdrawals'), 'GET', '/wallet/withdrawals');
        $this->check($this->getJson('/api/v1/wallet/movements'), 'GET', '/wallet/movements');
        $this->check($this->getJson('/api/v1/wallet/recharges/'.WalletRecharge::value('id')), 'GET', '/wallet/recharges/{recharge}');
        $this->check($this->getJson('/api/v1/wallet/withdrawals/'.WalletWithdrawal::value('id')), 'GET', '/wallet/withdrawals/{withdrawal}');
        $this->check($this->putJson('/api/v1/auth/push-token', ['token' => str_repeat('f', 40), 'platform' => 'android']), 'PUT', '/auth/push-token');
        $this->check($this->getJson('/api/v1/auth/devices'), 'GET', '/auth/devices');
        $this->check($this->getJson('/api/v1/wallet/settlements'), 'GET', '/wallet/settlements');

        $list = $this->getJson('/api/v1/notifications');
        $this->check($list, 'GET', '/notifications');
        $this->check($this->getJson('/api/v1/notifications/unread-count'), 'GET', '/notifications/unread-count');
        $this->check($this->postJson('/api/v1/notifications/'.$list->json('data.0.id').'/read'), 'POST', '/notifications/{notification}/read');
    }

    // ── Utilidades ──────────────────────────────────────────────

    protected function bearer(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $token = $user->createToken('spec', ApiAbilities::for($user), now()->addDay())->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token);
    }

    /** La respuesta tiene el estado esperado y su cuerpo cumple el esquema documentado. */
    protected function check(TestResponse $response, string $method, string $path, int $status = 200): void
    {
        $response->assertStatus($status);

        $operation = $this->specOperations()[$method.' '.$path] ?? null;
        $this->assertNotNull($operation, "{$method} {$path} no está en la especificación");

        $documented = $operation['responses'][(string) $status] ?? null;
        $this->assertNotNull($documented, "{$method} {$path} no documenta la respuesta {$status}");

        $schema = $this->deref($documented)['content']['application/json']['schema'] ?? null;
        $this->assertNotNull($schema, "{$method} {$path} {$status} sin esquema JSON");

        $errors = $this->validate(json_decode($response->getContent(), true), $schema, '$');
        $this->assertSame([], $errors, "{$method} {$path} {$status} no cumple la especificación");
    }

    protected function resolve(string $ref): ?array
    {
        $node = $this->spec();

        foreach (explode('/', substr($ref, 2)) as $part) {
            if (! is_array($node) || ! array_key_exists($part, $node)) {
                return null;
            }
            $node = $node[$part];
        }

        return $node;
    }

    protected function deref(array $node): array
    {
        while (isset($node['$ref'])) {
            $node = $this->resolve($node['$ref']);
        }

        return $node;
    }

    /** @return list<string> errores encontrados, vacío si cumple */
    protected function validate(mixed $value, array $schema, string $at): array
    {
        $schema = $this->deref($schema);
        $errors = [];

        foreach ($schema['allOf'] ?? [] as $part) {
            $errors = [...$errors, ...$this->validate($value, $part, $at)];
        }

        if (isset($schema['oneOf'])) {
            $matches = array_filter($schema['oneOf'], fn ($option) => $this->validate($value, $option, $at) === []);
            if (count($matches) !== 1) {
                $errors[] = "{$at}: cumple ".count($matches).' opciones de oneOf';
            }
        }

        if (isset($schema['type']) && ! $this->hasType($value, (array) $schema['type'])) {
            return [...$errors, "{$at}: se esperaba ".implode('|', (array) $schema['type']).', llegó '.get_debug_type($value)];
        }

        if (array_key_exists('const', $schema) && $value !== $schema['const']) {
            $errors[] = "{$at}: se esperaba ".json_encode($schema['const']);
        }

        if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            $errors[] = "{$at}: ".json_encode($value).' no está en el enum';
        }

        if (is_array($value)) {
            foreach ($schema['required'] ?? [] as $key) {
                if (! array_key_exists($key, $value)) {
                    $errors[] = "{$at}: falta '{$key}'";
                }
            }

            foreach ($schema['properties'] ?? [] as $key => $property) {
                if (array_key_exists($key, $value)) {
                    $errors = [...$errors, ...$this->validate($value[$key], $property, "{$at}.{$key}")];
                }
            }

            if (isset($schema['items']) && array_is_list($value)) {
                foreach ($value as $i => $item) {
                    $errors = [...$errors, ...$this->validate($item, $schema['items'], "{$at}[{$i}]")];
                }
            }
        }

        return $errors;
    }

    protected function hasType(mixed $value, array $types): bool
    {
        foreach ($types as $type) {
            $ok = match ($type) {
                'null' => $value === null,
                'boolean' => is_bool($value),
                'integer' => is_int($value),
                'number' => is_int($value) || is_float($value),
                'string' => is_string($value),
                // json_decode(..., true) deja {} y [] iguales: un arreglo vacío vale para los dos.
                'array' => is_array($value) && array_is_list($value),
                'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
                default => false,
            };

            if ($ok) {
                return true;
            }
        }

        return false;
    }
}

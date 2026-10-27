<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsCheckoutData;
use Tests\Concerns\PassesAdminTwoFactor;
use Tests\TestCase;

/**
 * Separación del panel admin: ningún customer ni seller entra a una ruta
 * admin aunque conozca la URL, y con ADMIN_DOMAIN el panel solo existe en su
 * host y con su propia cookie de sesión.
 */
class AdminAccessTest extends TestCase
{
    use BuildsCheckoutData;
    use PassesAdminTwoFactor;
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    /** Todas las rutas protegidas por el middleware 'admin'. */
    protected function adminRoutes(): array
    {
        $routes = collect($this->app['router']->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('admin', $route->gatherMiddleware(), true))
            ->values()
            ->all();

        $this->assertNotEmpty($routes);

        return $routes;
    }

    /** URI de la ruta con sus parámetros rellenos, sin host. */
    protected function uriFor(Route $route): string
    {
        return '/'.ltrim(preg_replace('/\{[^}]+\}/', '1', $route->uri()), '/');
    }

    protected function methodFor(Route $route): string
    {
        return in_array('GET', $route->methods(), true) ? 'GET' : $route->methods()[0];
    }

    /** Vuelve a registrar routes/web.php con otra configuración de admin. */
    protected function useAdminDomain(?string $domain, bool $enforce = true): void
    {
        config(['admin.domain' => $domain, 'admin.enforce_host' => $enforce]);

        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        // Igual que bootstrap/app.php: el archivo va dentro del grupo 'web'.
        $router->middleware('web')->group(base_path('routes/web.php'));
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }

    // ── Orden de middleware ───────────────────────────────────────

    public function test_admin_middleware_always_runs_after_auth(): void
    {
        foreach ($this->adminRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            $auth = array_search('auth', $middleware, true);
            $admin = array_search('admin', $middleware, true);

            $this->assertNotFalse($auth, "{$route->uri()} no lleva 'auth'");
            $this->assertLessThan($admin, $auth, "{$route->uri()}: 'admin' va antes que 'auth'");
        }
    }

    // ── Roles sin acceso ─────────────────────────────────────────

    public function test_guest_is_sent_to_the_admin_login_on_every_admin_route(): void
    {
        foreach ($this->adminRoutes() as $route) {
            $this->call($this->methodFor($route), $this->uriFor($route))
                ->assertRedirect('/admin/login');
        }
    }

    public function test_customer_is_forbidden_on_every_admin_route(): void
    {
        $customer = $this->makeCustomer();

        foreach ($this->adminRoutes() as $route) {
            $this->actingAs($customer)
                ->call($this->methodFor($route), $this->uriFor($route))
                ->assertForbidden();
        }
    }

    public function test_seller_is_forbidden_on_every_admin_route(): void
    {
        $seller = $this->makeSeller();

        foreach ($this->adminRoutes() as $route) {
            $this->actingAs($seller)
                ->call($this->methodFor($route), $this->uriFor($route))
                ->assertForbidden();
        }
    }

    public function test_the_warehouse_is_an_admin_route(): void
    {
        $uris = array_map(fn (Route $route) => $route->uri(), $this->adminRoutes());

        $this->assertContains('admin/warehouse', $uris);
        $this->assertContains('admin/warehouse/orders/{id}/dispatch', $uris);
        $this->assertContains('admin/warehouse/orders/{id}/deliver', $uris);
    }

    // ── Login del panel ──────────────────────────────────────────

    public function test_admin_login_rejects_non_admin_accounts_with_the_generic_error(): void
    {
        $seller = $this->makeSeller();
        $seller->forceFill(['password' => Hash::make('secreto123')])->save();

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => $seller->email, 'password' => 'secreto123'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => __('Correo o contraseña incorrectos.')]);

        $this->assertGuest();
    }

    public function test_admin_can_log_into_the_panel(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['password' => Hash::make('secreto123')])->save();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'secreto123'])
            ->assertRedirect(route('admin.home'));

        $this->assertAuthenticatedAs($admin);
    }

    // ── Host ─────────────────────────────────────────────────────

    public function test_enforced_host_without_a_domain_closes_the_panel(): void
    {
        config(['admin.enforce_host' => true]);

        $this->actingAsAdmin($this->makeAdmin())
            ->get('/admin/warehouse')
            ->assertNotFound();
    }

    public function test_with_a_domain_the_panel_only_exists_on_the_admin_host(): void
    {
        $this->useAdminDomain('admin.tienda.test');
        $admin = $this->makeAdmin();

        $this->actingAsAdmin($admin)->get('http://localhost/admin/warehouse')->assertNotFound();
        $this->actingAsAdmin($admin)->get('http://localhost/admin/shops')->assertNotFound();

        $this->actingAs($this->makeSeller())
            ->get('http://admin.tienda.test/admin/shops')
            ->assertForbidden();

        $this->assertStringStartsWith('http://admin.tienda.test/admin/', route('admin.shops'));
    }

    public function test_the_admin_host_uses_its_own_strict_session_cookie(): void
    {
        $this->useAdminDomain('admin.tienda.test');

        $response = $this->get('http://admin.tienda.test/admin/login')->assertOk();

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === config('admin.session_cookie'));

        $this->assertNotNull($cookie, 'el host admin no emitió su propia cookie de sesión');
        $this->assertNull($cookie->getDomain());
        $this->assertSame('strict', strtolower((string) $cookie->getSameSite()));
    }

    public function test_storefront_routes_on_the_admin_host_bounce_to_the_shop(): void
    {
        $this->useAdminDomain('admin.tienda.test');

        $this->get('http://admin.tienda.test/cart?x=1')
            ->assertRedirect(rtrim(config('app.url'), '/').'/cart?x=1');

        $this->post('http://admin.tienda.test/subscribers', ['email' => 'a@b.test'])
            ->assertNotFound();
    }
}

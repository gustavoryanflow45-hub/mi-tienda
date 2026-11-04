<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Support\AdminRoutes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsCheckoutData;
use Tests\Concerns\PassesAdminTwoFactor;
use Tests\TestCase;

/**
 * Separación del panel admin: el panel autentica con su propio guard
 * ('admin', tabla admins), así que ningún usuario de la tienda —customer,
 * seller, ni siquiera una fila de users con user_type = 'admin'— entra a una
 * ruta admin aunque conozca la URL. Con ADMIN_DOMAIN el panel solo existe en
 * su host; en ambos modos usa su propia cookie de sesión.
 */
class AdminAccessTest extends TestCase
{
    use BuildsCheckoutData;
    use PassesAdminTwoFactor;
    use RefreshDatabase;

    protected function makeAdmin(): Admin
    {
        return Admin::factory()->create();
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

    /** Vuelve a registrar las rutas con otra configuración de admin. */
    protected function useAdminDomain(?string $domain, bool $enforce = true, ?string $path = null): void
    {
        config(['admin.domain' => $domain, 'admin.enforce_host' => $enforce]);

        if ($path !== null) {
            config(['admin.path' => $path]);
        }

        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        // Igual que bootstrap/app.php: web.php dentro del grupo 'web' y el
        // panel a través de AdminRoutes.
        $router->middleware('web')->group(base_path('routes/web.php'));
        AdminRoutes::register();
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }

    // ── Orden de middleware ───────────────────────────────────────

    public function test_admin_middleware_always_runs_after_the_admin_guard(): void
    {
        foreach ($this->adminRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            $auth = array_search('auth:admin', $middleware, true);
            $admin = array_search('admin', $middleware, true);

            $this->assertNotFalse($auth, "{$route->uri()} no lleva 'auth:admin'");
            $this->assertNotContains('auth', $middleware, "{$route->uri()} usa el guard de la tienda");
            $this->assertLessThan($admin, $auth, "{$route->uri()}: 'admin' va antes que 'auth:admin'");
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

    /**
     * Una sesión de la tienda no es una sesión del panel: para el guard
     * 'admin' es un invitado, así que acaba en el login del panel.
     */
    public function test_storefront_users_of_any_role_are_guests_of_the_panel(): void
    {
        $legacyAdmin = User::factory()->create(['user_type' => 'admin']);

        foreach ([$this->makeCustomer(), $this->makeSeller(), $legacyAdmin] as $user) {
            foreach ($this->adminRoutes() as $route) {
                $this->actingAs($user)
                    ->call($this->methodFor($route), $this->uriFor($route))
                    ->assertRedirect('/admin/login');
            }
        }
    }

    public function test_the_warehouse_is_an_admin_route(): void
    {
        $uris = array_map(fn (Route $route) => $route->uri(), $this->adminRoutes());

        $this->assertContains('admin/warehouse', $uris);
        $this->assertContains('admin/warehouse/orders/{id}/dispatch', $uris);
        $this->assertContains('admin/warehouse/orders/{id}/deliver', $uris);
    }

    // ── routes/admin.php ─────────────────────────────────────────

    public function test_every_admin_route_comes_from_routes_admin_and_is_named_admin(): void
    {
        $adminControllers = 'App\\Http\\Controllers\\Admin\\';

        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            $isPanel = str_starts_with((string) $route->getActionName(), $adminControllers)
                || str_starts_with((string) $route->getName(), 'admin.');

            if (! $isPanel) {
                continue;
            }

            $this->assertStringStartsWith('admin.', (string) $route->getName(), "{$route->uri()} sin nombre admin.*");
            $this->assertStringStartsWith('admin', $route->uri(), "{$route->getName()} fuera del prefijo del panel");
        }

        $this->assertStringNotContainsString('Admin\\', file_get_contents(base_path('routes/web.php')));
    }

    public function test_admin_path_moves_the_whole_panel(): void
    {
        $this->useAdminDomain(null, false, 'gestion');

        $this->get('/gestion/login')->assertOk();
        $this->get('/admin/login')->assertNotFound();
        $this->get('/gestion/shops')->assertRedirect('/gestion/login');
        $this->assertSame(url('/gestion/warehouse'), route('admin.warehouse.index'));
    }

    public function test_an_empty_admin_path_without_a_domain_falls_back_to_admin(): void
    {
        $this->useAdminDomain(null, false, '');

        $this->get('/admin/login')->assertOk();
        $this->get('/')->assertOk();
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

        $this->assertGuest('admin');
    }

    public function test_a_users_row_with_user_type_admin_cannot_log_into_the_panel(): void
    {
        $user = User::factory()->create(['user_type' => 'admin']);
        $user->forceFill(['password' => Hash::make('secreto123')])->save();

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => $user->email, 'password' => 'secreto123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_admin_can_log_into_the_panel(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['password' => Hash::make('secreto123')])->save();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'secreto123'])
            ->assertRedirect(route('admin.home'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    public function test_changing_the_password_closes_the_admins_other_sessions(): void
    {
        $admin = $this->makeAdmin();

        // Sesión abierta con la contraseña de antes ('auth.session' guarda su hash).
        $this->actingAsAdmin($admin)
            ->withSession(['password_hash_admin' => $admin->getAuthPassword()])
            ->get('/admin/shops')->assertOk();

        $admin->forceFill(['password' => 'Otra#Clave2026'])->save();

        $this->get('/admin/shops')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }

    public function test_without_a_domain_the_panel_still_uses_its_own_cookie_scoped_to_its_path(): void
    {
        $admin = $this->get('/admin/login')->assertOk();
        $shop = $this->get('/login')->assertOk();

        $panelCookie = collect($admin->headers->getCookies())
            ->first(fn ($c) => $c->getName() === config('admin.session_cookie'));

        $this->assertNotNull($panelCookie, 'el panel no emitió su propia cookie de sesión');
        $this->assertSame('/admin', $panelCookie->getPath());
        $this->assertSame('strict', strtolower((string) $panelCookie->getSameSite()));

        $shopCookies = collect($shop->headers->getCookies())->map->getName();
        $this->assertNotContains(config('admin.session_cookie'), $shopCookies);
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

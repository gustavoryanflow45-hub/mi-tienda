<?php

namespace Tests\Feature\Api\V1;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\Concerns\PassesAdminTwoFactor;
use Tests\TestCase;

/**
 * La API es solo para compradores y vendedores: ninguna ruta de /api toca
 * el panel admin, y ni un token de la API abre el panel ni una sesión del
 * panel abre la API.
 */
class ApiRoutesTest extends TestCase
{
    use PassesAdminTwoFactor;
    use RefreshDatabase;

    /** Endpoints públicos (sin token); todo lo demás exige auth:sanctum. */
    protected const PUBLIC = [
        'api.v1.auth.register', 'api.v1.auth.login',
        'api.v1.auth.email.resend', 'api.v1.auth.password.forgot',
    ];

    /** @return Route[] */
    protected function apiRoutes(): array
    {
        $routes = collect($this->app['router']->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => str_starts_with($route->uri(), 'api/'))
            ->values()
            ->all();

        $this->assertNotEmpty($routes);

        return $routes;
    }

    public function test_every_api_route_is_versioned_and_never_touches_the_panel(): void
    {
        foreach ($this->apiRoutes() as $route) {
            $name = (string) $route->getName();
            $middleware = $route->gatherMiddleware();

            $this->assertStringStartsWith('api/v1/', $route->uri());
            $this->assertStringStartsWith('api.v1.', $name, "{$route->uri()} sin nombre api.v1.*");
            $this->assertStringStartsWith('App\\Http\\Controllers\\Api\\V1\\', $route->getActionName());

            foreach (['admin', 'admin.2fa', 'auth:admin', 'web', 'auth'] as $forbidden) {
                $this->assertNotContains($forbidden, $middleware, "{$route->uri()} usa '{$forbidden}'");
            }
            $this->assertEmpty(array_filter($middleware, fn ($m) => str_starts_with((string) $m, 'permission:')));

            if (! in_array($name, self::PUBLIC, true)) {
                $this->assertContains('auth:sanctum', $middleware, "{$route->uri()} sin auth:sanctum");
                $this->assertContains('api.active', $middleware, "{$route->uri()} sin api.active");
            }
        }
    }

    public function test_panel_accounts_cannot_hold_api_tokens(): void
    {
        $this->assertFalse(method_exists(Admin::class, 'createToken'));
        $this->assertSame([], config('sanctum.guard'));
    }

    public function test_an_api_token_does_not_open_the_panel(): void
    {
        $user = User::factory()->create(['user_type' => 'admin']);
        $token = $user->createToken('x', ['*'], now()->addDay())->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/admin/shops')
            ->assertRedirect('/admin/login');
    }

    public function test_a_panel_session_does_not_open_the_api(): void
    {
        $this->actingAsAdmin(Admin::factory()->create())
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }
}

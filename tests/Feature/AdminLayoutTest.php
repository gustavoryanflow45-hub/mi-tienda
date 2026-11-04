<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\BuildsCheckoutData;
use Tests\Concerns\PassesAdminTwoFactor;
use Tests\TestCase;

/**
 * El panel tiene su propio layout (layouts/admin) y sus vistas viven en
 * resources/views/admin/; la tienda no enlaza a ninguna ruta del panel.
 */
class AdminLayoutTest extends TestCase
{
    use BuildsCheckoutData;
    use PassesAdminTwoFactor;
    use RefreshDatabase;

    /** Marcas de la cabecera de la tienda que el panel no debe arrastrar. */
    protected const STOREFRONT_MARKERS = ['nav-cart-dropdown', 'loadMiniCart', 'search.ajax'];

    public function test_every_panel_page_uses_the_admin_layout_without_storefront_chrome(): void
    {
        $admin = Admin::factory()->create();

        foreach (['/admin/shops', '/admin/settlements', '/admin/wallet', '/admin/warehouse'] as $uri) {
            $response = $this->actingAsAdmin($admin)->get($uri)->assertOk();

            $response->assertSee(route('admin.logout'), false)
                ->assertSee('noindex, nofollow', false)
                ->assertSee($admin->name);

            foreach (self::STOREFRONT_MARKERS as $marker) {
                $response->assertDontSee($marker, false);
            }
        }
    }

    public function test_login_and_challenge_use_the_admin_layout_without_the_panel_nav(): void
    {
        $this->get('/admin/login')->assertOk()
            ->assertSee(route('admin.language'), false)
            ->assertDontSee(route('admin.shops'), false)
            ->assertDontSee('nav-cart-dropdown', false);

        // Dentro pero sin el segundo factor: solo salir, sin secciones.
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get('/admin/two-factor')->assertOk()
            ->assertSee(route('admin.logout'), false)
            ->assertDontSee(route('admin.shops'), false);
    }

    public function test_no_panel_view_extends_the_storefront_layout_or_its_partials(): void
    {
        $files = array_merge(
            File::allFiles(resource_path('views/admin')),
            [new \SplFileInfo(resource_path('views/layouts/admin.blade.php'))],
        );

        foreach ($files as $file) {
            $source = file_get_contents($file->getPathname());

            $this->assertStringNotContainsString("@extends('layouts.app')", $source, $file->getFilename());
            $this->assertDoesNotMatchRegularExpression(
                "/@include\('partials\.(header|topbar|footer|top-banner|flash|admin-nav)'/",
                $source,
                $file->getFilename().' incluye un parcial de la tienda'
            );
        }

        $this->assertFileDoesNotExist(resource_path('views/pages/warehouse.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/partials/admin-nav.blade.php'));
    }

    public function test_no_storefront_view_links_to_the_panel(): void
    {
        $views = str_replace('\\', '/', resource_path('views')).'/';

        foreach (File::allFiles(resource_path('views')) as $file) {
            $relative = substr(str_replace('\\', '/', $file->getPathname()), strlen($views));

            if (str_starts_with($relative, 'admin/') || $relative === 'layouts/admin.blade.php') {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                "/route\(\s*'admin\./",
                file_get_contents($file->getPathname()),
                "{$relative} enlaza al panel"
            );
        }
    }

    public function test_the_seller_dashboard_shows_no_panel_entries_even_for_a_legacy_admin_row(): void
    {
        $legacyAdmin = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($legacyAdmin)->get('/dashboard')->assertOk()
            ->assertDontSee('/admin/warehouse', false)
            ->assertDontSee('/admin/shops', false)
            ->assertDontSee('/admin/settlements', false);
    }

    public function test_the_nav_counts_pending_shops(): void
    {
        $this->makePendingSeller();
        $this->makePendingSeller();

        $this->actingAsAdmin(Admin::factory()->create())->get('/admin/wallet')->assertOk()
            ->assertSeeInOrder([__('Tiendas'), '<span class="count"', '>2</span>'], false);
    }

    public function test_the_panel_language_lives_in_the_panel_session(): void
    {
        $this->from('/admin/login')->post('/admin/language', ['locale' => 'en'])
            ->assertRedirect('/admin/login');

        $this->assertSame('en', session('locale'));
        $this->get('/admin/login')->assertOk()->assertSee('Admin panel sign-in');
    }

    public function test_a_page_with_its_own_alerts_shows_a_flash_only_once(): void
    {
        $seller = $this->makePendingSeller();

        $html = $this->actingAsAdmin(Admin::factory()->create())
            ->from('/admin/shops')
            ->followingRedirects()
            ->post("/admin/shops/{$seller->shop->id}/approve")
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, e($seller->shop->name).'» aprobada'));
    }
}

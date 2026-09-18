<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * Cambiar de idioma tiene que cambiar de verdad las pantallas del comprador,
 * no solo la barra superior.
 */
class LocalizationTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    public function test_switching_to_english_translates_the_storefront(): void
    {
        $this->withSession(['locale' => 'en']);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Top 10 categories', $home);
        $this->assertStringContainsString('Terms &amp; conditions', $home);
        $this->assertStringContainsString('I am shopping for...', $home);
        $this->assertStringNotContainsString('Top 10 categorías', $home);

        $this->get('/login')->assertOk()->assertSee('Welcome back!')->assertDontSee('Bienvenido');
        $this->get('/products')->assertOk()->assertSee('All products');
        $this->get('/password/reset')->assertOk()->assertSee('Reset password');
    }

    public function test_spanish_is_the_default_and_keeps_its_copy(): void
    {
        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Top 10 categorías', $home);
        $this->assertStringContainsString('Términos y condiciones', $home);
        $this->assertStringContainsString('Estoy buscando...', $home);

        $this->get('/login')->assertOk()->assertSee('¡Bienvenido de nuevo!');
    }

    public function test_order_status_labels_follow_the_locale(): void
    {
        $customer = $this->makeCustomer();
        $seller = $this->makeSeller();
        $order = $this->makePendingOrder($customer, $seller);
        $order->markPaid('stripe', 'pi_i18n');
        $order->update(['delivery_status' => 'on_the_way']);

        $this->actingAs($customer)->get('/orders')
            ->assertOk()
            ->assertSee('En camino')
            ->assertSee('Pagado')
            ->assertDontSee('On The Way');

        $this->actingAs($customer)->withSession(['locale' => 'en'])->get('/orders')
            ->assertOk()
            ->assertSee('On the way')
            ->assertSee('Paid')
            ->assertDontSee('En camino');
    }

    /** Sin lang/es/validation.php los errores de formulario salían en inglés. */
    public function test_validation_errors_follow_the_locale(): void
    {
        $this->post('/register', ['email' => 'no-es-correo'])
            ->assertSessionHasErrors(['email' => 'El campo correo electrónico debe ser un correo electrónico válido.']);

        $this->withSession(['locale' => 'en'])->post('/register', ['email' => 'no-es-correo'])
            ->assertSessionHasErrors(['email' => 'The email address field must be a valid email address.']);
    }

    public function test_controller_flash_messages_follow_the_locale(): void
    {
        $seller = $this->makeSeller();

        $this->actingAs($seller)->withSession(['locale' => 'en'])
            ->post('/wallet/withdraw', ['amount' => 999, 'full_name' => 'x', 'bank_name' => 'TRC20', 'account_number' => 'y'])
            ->assertSessionHasErrors(['amount' => 'Insufficient balance.']);

        $this->actingAs($seller)->withSession(['locale' => 'es'])
            ->post('/wallet/withdraw', ['amount' => 999, 'full_name' => 'x', 'bank_name' => 'TRC20', 'account_number' => 'y'])
            ->assertSessionHasErrors(['amount' => 'Saldo insuficiente.']);
    }

    /** Todas las claves __('...') de las vistas del comprador tienen traducción. */
    public function test_every_storefront_key_has_an_english_translation(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);
        $missing = [];

        foreach (glob(resource_path('views/{*,*/*,*/*/*}.blade.php'), GLOB_BRACE) as $view) {
            preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*[,)]/", file_get_contents($view), $m);
            foreach ($m[1] as $key) {
                $key = str_replace("\\'", "'", $key);
                if (preg_match('/^(dashboard|topbar|passwords)\./', $key)) {
                    continue; // claves de archivo php
                }
                if (! array_key_exists($key, $en)) {
                    $missing[$key] = basename($view);
                }
            }
        }

        $this->assertSame([], $missing, 'Claves sin traducir en lang/en.json: '.json_encode($missing, JSON_UNESCAPED_UNICODE));
    }
}

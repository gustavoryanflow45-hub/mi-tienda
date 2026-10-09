<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * GET /my-products: "Mis productos" del menú, lo que cada usuario fue
 * agregando a su carrito.
 */
class MyProductsTest extends TestCase
{
    use BuildsCheckoutData, RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/my-products')->assertRedirect(route('login'));
    }

    public function test_shows_only_the_products_in_the_users_own_cart(): void
    {
        $seller = $this->makeSeller();
        $customer = $this->makeCustomer();
        $other = $this->makeCustomer();

        $mine = $this->makeProduct($seller, 20);
        $mine->update(['name' => 'Zapatillas que agregué']);
        $theirs = $this->makeProduct($seller);
        $theirs->update(['name' => 'Producto de otro cliente']);

        $this->addToCart($customer, $mine, 2);
        $this->addToCart($customer, $mine, 1);   // otra línea del mismo producto
        $this->addToCart($other, $theirs);

        $response = $this->actingAs($customer)->get('/my-products');

        $response->assertOk()
            ->assertSee('Zapatillas que agregué')
            ->assertDontSee('Producto de otro cliente')
            ->assertSee('3 unidades')
            ->assertSee('$60.00');

        // Las dos líneas del mismo producto son una sola tarjeta.
        $this->assertSame(1, substr_count($response->getContent(), 'class="my-card"'));
    }

    public function test_empty_cart_says_so(): void
    {
        $this->actingAs($this->makeCustomer())
            ->get('/my-products')
            ->assertOk()
            ->assertSee('Todavía no agregaste productos.', false);
    }

    public function test_menu_links_to_my_products(): void
    {
        $this->get('/')->assertSee(route('my-products.index'), false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * La talla elegida se guarda como una clave interna ("9-negro") y hasta
 * ahora era esa clave la que llegaba a la pantalla. Aquí se cubre que cada
 * pantalla que toca una línea con variante la muestre legible, y que el
 * almacén y el vendedor lleguen a verla: son quienes separan la caja.
 */
class VariantVisibilityTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    /** Zapatilla de categoría footwear: sus tallas se rotulan "Talla US". */
    protected function makeShoe(User $seller): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'zapatos'],
            ['name' => 'Zapatos', 'status' => 1, 'variant_type' => 'footwear'],
        );

        $product = Product::create([
            'name' => 'Zapatilla de prueba',
            'slug' => 'zapatilla-'.uniqid(),
            'category_id' => $category->id,
            'added_by' => $seller->id,
            'unit_price' => 59.90,
            'variant_product' => 1,
        ]);

        ProductStock::create([
            'product_id' => $product->id,
            'size' => '9', 'color' => 'negro',
            'price' => 59.90, 'qty' => 5,
        ]);

        return $product->load('stocks', 'category');
    }

    /** Pedido pagado de una zapatilla talla 9 negra, ya en el almacén. */
    protected function makeWarehouseOrder(User $customer, User $seller): Order
    {
        $product = $this->makeShoe($seller);

        $order = Order::create([
            'user_id' => $customer->id,
            'code' => 'ORD-TEST-'.strtoupper(uniqid()),
            'status' => 'pendiente',
            'payment_status' => 'paid',
            'delivery_status' => 'warehouse',
            'warehouse_at' => now(),
            'subtotal' => 59.90,
            'shipping_total' => 0,
            'tax_amount' => 0,
            'grand_total' => 59.90,
            'shipping_address' => $this->completeShippingAddress($customer),
        ]);

        $order->orderDetails()->create([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'variation' => '9-negro',
            'product_name' => $product->name,
            'price' => 59.90,
            'quantity' => 1,
            'delivery_status' => 'warehouse',
            'payment_status' => 'paid',
        ]);

        return $order;
    }

    public function test_parse_variant_is_the_inverse_of_build_variant(): void
    {
        $this->assertSame(
            ['size' => '9', 'color' => 'negro'],
            ProductStock::parseVariant('9-negro'),
        );

        $this->assertSame(
            ['size' => null, 'color' => null],
            ProductStock::parseVariant(null),
        );
    }

    /**
     * Una clave de una parte no dice qué es: "M" es talla y "negro" color.
     * Se resuelve contra la paleta, no adivinando por la pinta del texto.
     */
    public function test_a_single_part_key_is_resolved_against_the_palette(): void
    {
        $this->assertSame(['size' => null, 'color' => 'negro'], ProductStock::parseVariant('negro'));
        $this->assertSame(['size' => 'M', 'color' => null], ProductStock::parseVariant('M'));
    }

    public function test_the_size_label_comes_from_the_product_type(): void
    {
        $shoe = $this->makeShoe($this->makeSeller());

        $this->assertSame(
            'Talla US 9 · Negro',
            ProductStock::describeVariant('9-negro', $shoe)['text'],
        );

        // Sin producto no hay tipo que consultar, y "Talla" es lo que aplica
        // a la mayoría: sigue siendo legible en vez de caer a la clave cruda.
        $this->assertSame('Talla 9 · Negro', ProductStock::describeVariant('9-negro')['text']);
    }

    public function test_the_cart_shows_the_size_instead_of_the_raw_key(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeShoe($this->makeSeller());

        $this->actingAs($customer)
            ->postJson(route('cart.add'), [
                'product_id' => $product->id,
                'variation' => '9-negro',
                'quantity' => 1,
            ])
            ->assertJson(['status' => 'success']);

        $response = $this->actingAs($customer)->get(route('cart.index'));

        $response->assertOk()
            ->assertSee('Talla US')
            ->assertSee('Negro')
            ->assertDontSee('9-negro');
    }

    public function test_the_mini_cart_shows_the_size(): void
    {
        $customer = $this->makeCustomer();
        $product = $this->makeShoe($this->makeSeller());

        $this->actingAs($customer)->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variation' => '9-negro',
        ]);

        $this->actingAs($customer)->get(route('cart.mini'))
            ->assertOk()
            ->assertSee('Talla US')
            ->assertSee('Negro')
            ->assertDontSee('9-negro');
    }

    /**
     * El almacén no listaba las líneas del pedido, solo el total: quien
     * despacha no tenía de dónde sacar qué talla meter en la caja.
     */
    public function test_the_warehouse_panel_lists_each_item_with_its_size(): void
    {
        $order = $this->makeWarehouseOrder($this->makeCustomer(), $this->makeSeller());

        $this->actingAs($this->makeAdmin())->get(route('warehouse.index'))
            ->assertOk()
            ->assertSee('Zapatilla de prueba')
            ->assertSee('Talla US')
            ->assertSee('Negro')
            ->assertDontSee('9-negro');
    }

    public function test_the_seller_panel_lists_each_item_with_its_size(): void
    {
        $seller = $this->makeSeller();
        $this->makeWarehouseOrder($this->makeCustomer(), $seller);

        $this->actingAs($seller)->get(route('seller.orders.index'))
            ->assertOk()
            ->assertSee('Zapatilla de prueba')
            ->assertSee('Talla US')
            ->assertSee('Negro')
            ->assertDontSee('9-negro');
    }

    /**
     * El modal de la tarjeta devolvía JSON mientras el JS lo inyectaba como
     * HTML, así que volcaba el JSON en pantalla y desde la rejilla no había
     * forma de elegir talla: el único camino era abrir la ficha completa.
     */
    public function test_the_quick_add_modal_returns_the_picker_and_not_json(): void
    {
        $product = $this->makeShoe($this->makeSeller());

        $response = $this->actingAs($this->makeCustomer())
            ->post(route('cart.modal'), ['id' => $product->id]);

        $response->assertOk();
        $this->assertStringContainsString('text/html', $response->headers->get('content-type'));

        $response->assertSee('qa-root', false)
            ->assertSee('Talla US')          // rótulo del tipo de la categoría
            ->assertSee('data-size="9"', false)
            ->assertSee('data-color="negro"', false);
    }

    /**
     * El modal y la ficha son el mismo selector en dos tamaños: si cada uno
     * armara su mapa por su cuenta, uno ofrecería combinaciones que el otro
     * ya no tiene. Los dos salen de Product::stockMap().
     */
    public function test_the_quick_add_modal_ships_the_same_stock_map_as_the_detail_page(): void
    {
        $product = $this->makeShoe($this->makeSeller());

        $this->assertSame(
            ['9-negro' => ['qty' => 5, 'price' => 59.90]],
            $product->stockMap(),
        );

        $this->actingAs($this->makeCustomer())
            ->post(route('cart.modal'), ['id' => $product->id])
            ->assertOk()
            ->assertSee('data-stock-map', false)
            ->assertSee(e(json_encode($product->stockMap())), false);
    }

    /** Un producto sin variantes abre el modal sin pedir que se elija nada. */
    public function test_the_quick_add_modal_of_a_flat_product_has_no_choices(): void
    {
        $product = $this->makeProduct($this->makeSeller());

        // Un producto plano no tiene combinaciones, pero sí una fila de stock:
        // sin ella el carrito lo da por agotado y el modal no ofrece el botón.
        ProductStock::create([
            'product_id' => $product->id,
            'price' => $product->unit_price,
            'qty' => 4,
        ]);

        $this->actingAs($this->makeCustomer())
            ->post(route('cart.modal'), ['id' => $product->id])
            ->assertOk()
            ->assertSee('qa-root', false)
            ->assertSee('Añadir al carrito')
            ->assertDontSee('data-size=', false)   // los chips, no el bloque <style>
            ->assertDontSee('data-color=', false)
            ->assertSee('data-has-sizes="0"', false)
            ->assertSee('data-has-colors="0"', false);
    }

    public function test_the_customer_order_detail_shows_the_size(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeWarehouseOrder($customer, $this->makeSeller());

        $this->actingAs($customer)->get(route('orders.show', $order->id))
            ->assertOk()
            ->assertSee('Talla US')
            ->assertSee('Negro')
            ->assertDontSee('9-negro');
    }
}

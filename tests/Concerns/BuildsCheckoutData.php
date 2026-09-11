<?php

namespace Tests\Concerns;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

trait BuildsCheckoutData
{
    protected function makeCustomer(): User
    {
        return User::factory()->create(['user_type' => 'customer']);
    }

    /** Seller operativo: con la tienda ya aprobada (shops.status = 1). */
    protected function makeSeller(): User
    {
        $seller = User::factory()->create(['user_type' => 'seller']);
        $this->makeShop($seller, 1);

        return $seller;
    }

    /** Seller recién registrado, con la tienda aún sin revisar. */
    protected function makePendingSeller(): User
    {
        $seller = User::factory()->create(['user_type' => 'seller']);
        $this->makeShop($seller, 0);

        return $seller;
    }

    protected function makeShop(User $seller, int $status = 1): Shop
    {
        return Shop::create([
            'user_id' => $seller->id,
            'name' => 'Tienda de '.$seller->name,
            'email' => 'tienda-'.$seller->id.'@example.com',
            'address' => 'Av. Siempre Viva 742',
            'id_front_image' => 'sellers/id/front.jpg',
            'id_back_image' => 'sellers/id/back.jpg',
            'status' => $status,
        ]);
    }

    protected function makeProduct(User $seller, float $price = 100.00): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General'],
        );

        return Product::create([
            'name' => 'Producto de prueba',
            'slug' => 'producto-de-prueba-'.uniqid(),
            'category_id' => $category->id,
            'added_by' => $seller->id,
            'unit_price' => $price,
        ]);
    }

    protected function addToCart(
        User $user,
        Product $product,
        int $quantity = 1,
        ?float $price = null,
        float $shippingCost = 0.0,
    ): Cart {
        return Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $price ?? $product->unit_price,
            'shipping_cost' => $shippingCost,
        ]);
    }

    protected function completeShippingAddress(User $customer): array
    {
        return [
            'full_name' => $customer->name,
            'phone' => '0999999999',
            'email' => $customer->email,
            'address' => 'Av. Siempre Viva 742',
            'city' => 'Quito',
            'state' => 'Pichincha',
            'country' => 'Ecuador',
            'postal_code' => '170101',
        ];
    }

    /**
     * Checkout a medio camino: el comprador ya pulsó "Pagar", así que existe
     * el pedido pendiente y el carrito que lo originó sigue cargado (los
     * controladores de pago recalculan los totales desde él).
     */
    protected function makePendingOrder(User $customer, User $seller, float $total = 100.00): Order
    {
        $product = $this->makeProduct($seller, $total);
        $this->addToCart($customer, $product, 1, $total);

        $order = Order::create([
            'user_id' => $customer->id,
            'code' => 'ORD-TEST-'.strtoupper(uniqid()),
            'status' => 'pendiente',
            'payment_status' => 'unpaid',
            'subtotal' => $total,
            'shipping_total' => 0,
            'tax_amount' => 0,
            'grand_total' => $total,
            'shipping_address' => $this->completeShippingAddress($customer),
        ]);

        $order->orderDetails()->create([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $total,
            'quantity' => 1,
        ]);

        return $order;
    }
}

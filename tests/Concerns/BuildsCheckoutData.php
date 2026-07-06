<?php

namespace Tests\Concerns;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

trait BuildsCheckoutData
{
    protected function makeCustomer(): User
    {
        return User::factory()->create(['user_type' => 'customer']);
    }

    protected function makeSeller(): User
    {
        return User::factory()->create(['user_type' => 'seller']);
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

    protected function addToCart(User $user, Product $product, int $quantity = 1, ?float $price = null): Cart
    {
        return Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $price ?? $product->unit_price,
        ]);
    }

    /**
     * Order in the same state CheckoutController@index leaves it:
     * pending, unpaid, with one detail row per product.
     */
    protected function makePendingOrder(User $customer, User $seller, float $total = 100.00): Order
    {
        $product = $this->makeProduct($seller, $total);

        $order = Order::create([
            'user_id' => $customer->id,
            'code' => 'ORD-TEST-'.strtoupper(uniqid()),
            'status' => 'pendiente',
            'payment_status' => 'unpaid',
            'subtotal' => $total,
            'grand_total' => $total,
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

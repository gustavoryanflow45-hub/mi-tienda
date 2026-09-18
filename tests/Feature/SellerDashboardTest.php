<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCheckoutData;
use Tests\TestCase;

/**
 * El panel del vendedor solo enlaza a lo que existe y sus cifras son reales.
 */
class SellerDashboardTest extends TestCase
{
    use BuildsCheckoutData;
    use RefreshDatabase;

    protected function makeVerifiedSeller(): User
    {
        $seller = $this->makeSeller();
        $seller->forceFill(['email_verified_at' => now()])->save();

        return $seller;
    }

    /** Solo el <ul> del menú lateral: el footer tiene sus propios href="#". */
    private function sidebar(string $html): string
    {
        $start = strpos($html, '<ul class="aiz-side-nav-list');
        $this->assertNotFalse($start, 'No se encontró el menú lateral');
        $end = strpos($html, '</ul>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_sidebar_has_no_dead_links(): void
    {
        $seller = $this->makeVerifiedSeller();

        $html = $this->actingAs($seller)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="#"', $this->sidebar($html));
        $this->assertStringContainsString(route('support-policy'), $html);

        foreach (['Reembolso', 'Cupones', 'por Mayor', 'Clasificados', 'Reseña', 'Visitantes'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, "«{$gone}» sigue en el panel");
        }
    }

    public function test_unverified_dashboard_has_no_dead_links_either(): void
    {
        $customer = $this->makeCustomer();
        $customer->forceFill(['email_verified_at' => null, 'email_verified' => 0])->save();

        $html = $this->actingAs($customer)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('href="#"', $this->sidebar($html));
    }

    /** "Pedidos por Confirmar" cuenta pedidos pagados que el vendedor aún no confirmó. */
    public function test_pending_orders_card_counts_paid_unconfirmed_orders_and_links_to_them(): void
    {
        $seller = $this->makeVerifiedSeller();
        $other = $this->makeVerifiedSeller();
        $customer = $this->makeCustomer();

        // Dos pagados sin confirmar del vendedor...
        $this->makePendingOrder($customer, $seller, 10.00)->markPaid('stripe', 'pi_a');
        $this->makePendingOrder($customer, $seller, 20.00)->markPaid('stripe', 'pi_b');
        // ...uno ya confirmado, uno sin pagar y uno de otro vendedor: no cuentan.
        $confirmed = $this->makePendingOrder($customer, $seller, 30.00);
        $confirmed->markPaid('stripe', 'pi_c');
        $confirmed->update(['delivery_status' => 'confirmed']);
        $confirmed->orderDetails()->update(['delivery_status' => 'confirmed']);
        $this->makePendingOrder($customer, $seller, 40.00);
        $this->makePendingOrder($customer, $other, 50.00)->markPaid('stripe', 'pi_d');

        $this->actingAs($seller)->get('/dashboard')
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['pending_orders'] === 2 && ! array_key_exists('visitors', $stats))
            ->assertSee('Pedidos por Confirmar')
            ->assertSee(route('seller.orders.index', ['delivery_status' => 'pending']), false);
    }
}

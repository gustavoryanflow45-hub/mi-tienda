<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Shop;
use App\Notifications\ShopStatusUpdatedNotification;

/**
 * Aprobar o rechazar la tienda de un vendedor. Lo usa el panel
 * (Admin\ShopController); no depende de ninguna petición HTTP.
 *
 * Cada decisión se audita y se notifica al vendedor (database + mail). Una
 * decisión que no cambia nada (aprobar una tienda ya aprobada) no escribe,
 * no audita y no notifica.
 */
class ShopApprovalService
{
    public function __construct(private AuditLog $audit) {}

    /** @return bool false si la tienda ya estaba aprobada. */
    public function approve(Shop $shop, Admin $admin): bool
    {
        return $this->decide($shop, Shop::APPROVED, 'shop.approved', $admin);
    }

    /** @return bool false si la tienda ya estaba rechazada. */
    public function reject(Shop $shop, Admin $admin): bool
    {
        return $this->decide($shop, Shop::REJECTED, 'shop.rejected', $admin);
    }

    protected function decide(Shop $shop, int $status, string $action, Admin $admin): bool
    {
        if ((int) $shop->status === $status) {
            return false;
        }

        $shop->update(['status' => $status]);
        $this->audit->record($action, $shop, ['seller_id' => $shop->user_id], $admin);

        $shop->user?->notify(new ShopStatusUpdatedNotification($shop, $status));

        return true;
    }
}

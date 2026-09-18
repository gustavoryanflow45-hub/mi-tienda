<?php

if (!function_exists('uploaded_asset')) {
    function uploaded_asset($path)
    {
        if (!$path) return asset('assets/img/placeholder.jpg');
        return asset('storage/' . $path);
    }
}

if (!function_exists('delivery_status_label')) {
    /**
     * Etiqueta legible de orders.delivery_status / order_details.delivery_status.
     * Las vistas hacían ucwords(str_replace('_', ' ', $status)) y mostraban
     * "On The Way" en una tienda en español; aquí pasa por __().
     */
    function delivery_status_label(?string $status): string
    {
        return match ($status) {
            'pending'    => __('Pendiente'),
            'confirmed'  => __('Confirmado'),
            'warehouse'  => __('En almacén'),
            'on_the_way' => __('En camino'),
            'delivered'  => __('Entregado'),
            'cancelled'  => __('Cancelado'),
            default      => ucwords(str_replace('_', ' ', (string) $status)),
        };
    }
}

if (!function_exists('payment_status_label')) {
    /** Etiqueta legible de orders.payment_status. */
    function payment_status_label(?string $status): string
    {
        return match ($status) {
            'paid'    => __('Pagado'),
            'unpaid'  => __('Sin pagar'),
            'partial' => __('Parcial'),
            default   => ucfirst((string) $status),
        };
    }
}

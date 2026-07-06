<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    public function initiate(Order $order, array $data): array;
    public function verify(array $payload): bool;
    public function name(): string;
    public function type(): string;
}
<?php

namespace App\Exceptions;

/**
 * El pedido ya tiene un cobro en curso o hecho en la pasarela: iniciar otro
 * podría cobrarle dos veces al comprador. La web responde 409 y la API
 * `409 payment_in_progress`.
 */
class PaymentInProgressException extends BusinessRuleException {}

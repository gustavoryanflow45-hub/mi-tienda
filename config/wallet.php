<?php

/*
|--------------------------------------------------------------------------
| Billetera
|--------------------------------------------------------------------------
|
| WALLET_MIN_WITHDRAWAL   Monto mínimo de un retiro, en la moneda de la
|                         tienda. Lo aplica WalletService::requestWithdrawal(),
|                         así que vale igual para /wallet en la web y para la
|                         API. Por defecto 1 (lo que la web ya exigía): hay que
|                         fijar el valor real antes de producción.
| WALLET_APP_NETWORK_FEE  Comisión de red de cada recarga y retiro pedidos
|                         desde la app (API): una recarga de X acredita X − fee
|                         al aprobarse y un retiro de X descuenta X del saldo y
|                         se transfieren X − fee. La web no la cobra.
*/

return [
    'min_withdrawal' => (float) env('WALLET_MIN_WITHDRAWAL', 1),

    'app_network_fee' => (float) env('WALLET_APP_NETWORK_FEE', 1),
];

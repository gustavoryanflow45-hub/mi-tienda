<?php

// config/services.php — agrega/reemplaza estas entradas.
//
// .env esperado (las credenciales de Stripe y PayPhone deben ser del mismo ambiente: prueba o producción):
//
//   STRIPE_KEY=pk_test_xxx              # pk_live_xxx en producción
//   STRIPE_SECRET=sk_test_xxx           # sk_live_xxx en producción
//   STRIPE_WEBHOOK_SECRET=whsec_xxx     # lo da el dashboard de Stripe al crear el webhook
//
//   PAYPHONE_TOKEN=xxxxxxxx             # token de la aplicación en PayPhone Developer (secreto)
//   PAYPHONE_STORE_ID=xxxxxxxx          # storeId de la misma aplicación
//   # No hay variable de ambiente: si es de prueba o real lo decide la
//   # aplicación de PayPhone a la que pertenece el token.
//
// Tras editar .env: php artisan config:clear (y config:cache solo en deploy).

return [

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'payphone' => [
        'token'    => env('PAYPHONE_TOKEN'),
        'store_id' => env('PAYPHONE_STORE_ID'),
    ],

];
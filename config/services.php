<?php

// config/services.php — agrega/reemplaza estas entradas.
//
// .env esperado (¡las credenciales DEBEN ser del mismo ambiente que KUSHKI_ENV!):
//
//   STRIPE_KEY=pk_test_xxx              # pk_live_xxx en producción
//   STRIPE_SECRET=sk_test_xxx           # sk_live_xxx en producción
//   STRIPE_WEBHOOK_SECRET=whsec_xxx     # lo da el dashboard de Stripe al crear el webhook
//
//   KUSHKI_ENV=test                     # 'test' o 'production' — EXPLÍCITO, nunca lo omitas
//   KUSHKI_PUBLIC_MERCHANT_ID=xxxxxxxx  # credencial pública DEL MISMO ambiente
//   KUSHKI_PRIVATE_MERCHANT_ID=xxxxxxxx # credencial privada DEL MISMO ambiente
//   KUSHKI_WEBHOOK_SECRET=xxxxxxxx      # si configuraste firma de webhooks en Kushki
//
// Tras editar .env: php artisan config:clear (y config:cache solo en deploy).

return [

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'kushki' => [
        'env'            => env('KUSHKI_ENV', 'test'), // default seguro: test
        'public_id'      => env('KUSHKI_PUBLIC_MERCHANT_ID'),
        'private_id'     => env('KUSHKI_PRIVATE_MERCHANT_ID'),
        'webhook_secret' => env('KUSHKI_WEBHOOK_SECRET'),
    ],

];
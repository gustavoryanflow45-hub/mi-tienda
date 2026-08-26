<?php

namespace App\Services\Payments;

use RuntimeException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeService
{
    private ?StripeClient $client = null;
    private ?string $secret;

    public function __construct(?string $secret)
    {
        $this->secret = $secret;
    }

    private function client(): StripeClient
    {
        if ($this->client === null) {
            if (empty($this->secret)) {
                throw new RuntimeException(
                    'STRIPE_SECRET no está definido. Revisa tu .env y ejecuta php artisan config:clear.'
                );
            }
            $this->client = new StripeClient($this->secret);
        }
        return $this->client;
    }

    public function createIntent(int $amountInCents, string $currency, array $metadata = []): PaymentIntent
    {
        return $this->client()->paymentIntents->create([
            'amount'   => $amountInCents,
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ]);
    }

    public function retrieveIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId);
    }

    public function updateIntent(string $paymentIntentId, array $params): PaymentIntent
    {
        return $this->client()->paymentIntents->update($paymentIntentId, $params);
    }
}

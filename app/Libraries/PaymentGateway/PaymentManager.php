<?php
namespace App\Libraries\PaymentGateway;

class PaymentManager
{
    public function gateway(string $gateway)
    {
        return match ($gateway) {
            'stripe' => new StripeGateway(),
            'paypal' => new PaypalGateway(),
            default  => throw new \Exception('Invalid gateway'),
        };
    }
}
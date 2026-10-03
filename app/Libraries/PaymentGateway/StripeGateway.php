<?php
namespace App\Libraries\PaymentGateway;

class StripeGateway
{
    public function checkout($payment)
    {
        \Stripe\Stripe::setApiKey(config('Stripe')->secretKey);

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $payment->description,
                    ],
                    'unit_amount' => $payment->amount * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $payment->success_url,
            'cancel_url'  => $payment->cancel_url,
        ]);

        return $session;
    }
}
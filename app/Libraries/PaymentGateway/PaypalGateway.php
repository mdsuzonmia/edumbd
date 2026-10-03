<?php

namespace App\Libraries\PaymentGateway;

class PaypalGateway
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;

    public function __construct(?string $clientId=null,?string $clientSecret=null,?bool $sandbox=null)
    {
        $this->clientId     = $clientId??(string) env('paypal.clientId', '');
        $this->clientSecret = $clientSecret??(string) env('paypal.clientSecret', '');

        if($sandbox===null){$sandboxValue=env('paypal.sandbox',strtolower((string)env('paypal.mode','sandbox'))!=='live');$sandbox=is_bool($sandboxValue)?$sandboxValue:(filter_var($sandboxValue,FILTER_VALIDATE_BOOL,FILTER_NULL_ON_FAILURE)??true);}

        $this->baseUrl = $sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * Get OAuth Access Token
     */
    protected function getAccessToken(): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $this->clientId . ':' . $this->clientSecret,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Accept-Language: en_US',
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (! isset($result['access_token'])) {
            throw new \Exception('Unable to obtain PayPal access token.');
        }

        return $result['access_token'];
    }

    /**
     * Create Checkout Order
     */
    public function createOrder(array $data): array
    {
        $accessToken = $this->getAccessToken();

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $data['payment_token'],
                    'description'  => $data['description'],
                    'amount'       => [
                        'currency_code' => $data['currency'],
                        'value'         => number_format($data['amount'], 2, '.', ''),
                    ],
                ],
            ],
            'application_context' => [
                'return_url' => $data['success_url'],
                'cancel_url' => $data['cancel_url'],
                'brand_name' => config('App')->appName ?? 'EduMark Pro',
                'user_action' => 'PAY_NOW',
            ],
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/v2/checkout/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (! isset($result['id'])) {
            throw new \Exception('Failed to create PayPal order.');
        }

        $approveUrl = null;

        foreach ($result['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveUrl = $link['href'];
                break;
            }
        }

        return [
            'order_id'    => $result['id'],
            'approve_url' => $approveUrl,
            'response'    => $result,
        ];
    }

    /**
     * Capture Payment
     */
    public function captureOrder(string $orderId): array
    {
        $accessToken = $this->getAccessToken();

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . "/v2/checkout/orders/{$orderId}/capture",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * Verify Order
     */
    public function getOrder(string $orderId): array
    {
        $accessToken = $this->getAccessToken();

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . "/v2/checkout/orders/{$orderId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}

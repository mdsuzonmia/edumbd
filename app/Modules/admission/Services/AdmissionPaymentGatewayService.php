<?php

namespace App\Modules\admission\Services;

use App\Libraries\PaymentGateway\PaypalGateway;
use App\Modules\admission\Models\AdmissionPaymentSettingModel;
use DomainException;
use Stripe\StripeClient;

final class AdmissionPaymentGatewayService
{
    private const ZERO_DECIMAL_CURRENCIES = [
        'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg',
        'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    public function settings(int $schoolId): object
    {
        return (new AdmissionPaymentSettingModel())->where('school_id',$schoolId)->first()??(object)['school_id'=>$schoolId,'currency'=>'BDT','manual_enabled'=>1,'manual_instructions'=>null,'stripe_enabled'=>0,'stripe_publishable_key'=>null,'stripe_secret_key'=>null,'paypal_enabled'=>0,'paypal_client_id'=>null,'paypal_client_secret'=>null,'paypal_sandbox'=>1];
    }

    public function availableGateways(int $schoolId): array
    {
        $settings=$this->settings($schoolId);$gateways=[];
        if(!empty($settings->manual_enabled))$gateways['manual']='Manual payment';
        if(!empty($settings->stripe_enabled)&&!empty($settings->stripe_secret_key))$gateways['stripe']='Credit/debit card (Stripe)';
        if(!empty($settings->paypal_enabled)&&!empty($settings->paypal_client_id)&&!empty($settings->paypal_client_secret))$gateways['paypal']='PayPal';
        return $gateways;
    }

    public function currency(int $schoolId): string{return strtoupper((string)$this->settings($schoolId)->currency);}

    public function saveSettings(int $schoolId,array $data,int $userId): void
    {
        $model=new AdmissionPaymentSettingModel();$existing=$model->where('school_id',$schoolId)->first();$cipher=new AdmissionCredentialCipher();
        foreach(['stripe_secret_key','paypal_client_id','paypal_client_secret'] as $field){$plain=trim((string)($data[$field]??''));if($plain!=='')$data[$field]=$cipher->encrypt($plain);elseif($existing)$data[$field]=$existing->{$field};else $data[$field]=null;}
        $data['school_id']=$schoolId;$data['updated_by']=$userId;if($existing)$model->update($existing->id,$data);else{$data['created_by']=$userId;$model->insert($data);}
    }

    public function createStripeCheckout(object $payment, string $description): object
    {
        $settings=$this->settings((int)$payment->school_id);$secret=!empty($settings->stripe_secret_key)?(new AdmissionCredentialCipher())->decrypt($settings->stripe_secret_key):'';
        if (empty($settings->stripe_enabled)||$secret === '') {
            throw new DomainException('Stripe is not configured.');
        }

        $stripe = new StripeClient($secret);
        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'client_reference_id' => $payment->token,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'product_data' => ['name' => $description],
                    'unit_amount' => self::minorUnitAmount((float) $payment->amount, $payment->currency),
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'admission_payment_token' => $payment->token,
                'application_id' => (string) $payment->application_id,
                'payment_type' => $payment->payment_type,
            ],
            'success_url' => base_url('admission/payment/'.$payment->token.'/stripe/success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => base_url('admission/payment/'.$payment->token.'/stripe/cancel'),
        ]);

        if (empty($session->id) || empty($session->url)) {
            throw new DomainException('Stripe did not return a checkout session.');
        }

        return $session;
    }

    public function verifyStripeCheckout(object $payment): array
    {
        $settings=$this->settings((int)$payment->school_id);$secret=!empty($settings->stripe_secret_key)?(new AdmissionCredentialCipher())->decrypt($settings->stripe_secret_key):'';
        if ($secret === '' || empty($payment->transaction_id)) {
            throw new DomainException('Stripe payment information is incomplete.');
        }

        $session = (new StripeClient($secret))->checkout->sessions->retrieve($payment->transaction_id, []);
        $metadataToken = (string) ($session->metadata->admission_payment_token ?? '');
        $expectedAmount = self::minorUnitAmount((float) $payment->amount, $payment->currency);

        if ($session->payment_status !== 'paid'
            || (int) $session->amount_total !== $expectedAmount
            || strtolower((string) $session->currency) !== strtolower($payment->currency)
            || $metadataToken !== $payment->token) {
            throw new DomainException('Stripe could not verify the completed payment.');
        }

        return [
            'transaction_id' => (string) ($session->payment_intent ?: $session->id),
            'provider_reference' => (string) $session->id,
            'payload' => $session->toArray(),
        ];
    }

    public function createPayPalCheckout(object $payment, string $description): array
    {
        $settings=$this->settings((int)$payment->school_id);$paypal=$this->paypal($settings,true);
        $order = $paypal->createOrder([
            'payment_token' => $payment->token,
            'description' => $description,
            'currency' => strtoupper($payment->currency),
            'amount' => (float) $payment->amount,
            'success_url' => base_url('admission/payment/'.$payment->token.'/paypal/success'),
            'cancel_url' => base_url('admission/payment/'.$payment->token.'/paypal/cancel'),
        ]);

        if (empty($order['order_id']) || empty($order['approve_url'])) {
            throw new DomainException('PayPal did not return an approval URL.');
        }

        return $order;
    }

    public function capturePayPalCheckout(object $payment, string $returnedOrderId): array
    {
        if (empty($payment->transaction_id) || !hash_equals((string) $payment->transaction_id, $returnedOrderId)) {
            throw new DomainException('The PayPal order does not match this payment.');
        }

        $settings=$this->settings((int)$payment->school_id);$paypal=$this->paypal($settings,false);
        $result = $paypal->captureOrder($returnedOrderId);
        if (($result['status'] ?? '') !== 'COMPLETED') {
            // A repeated return request may reach us after PayPal captured the
            // order but before our local transaction committed.
            $result = $paypal->getOrder($returnedOrderId);
        }
        $unit = $result['purchase_units'][0] ?? [];
        $capture = $unit['payments']['captures'][0] ?? [];
        $amount = $capture['amount'] ?? ($unit['amount'] ?? []);

        if (($result['status'] ?? '') !== 'COMPLETED'
            || ($unit['reference_id'] ?? '') !== $payment->token
            || strtoupper((string) ($amount['currency_code'] ?? '')) !== strtoupper($payment->currency)
            || !self::amountsMatch((float) ($amount['value'] ?? -1), (float) $payment->amount)) {
            throw new DomainException('PayPal could not verify the completed payment.');
        }

        return [
            'transaction_id' => (string) ($capture['id'] ?? $returnedOrderId),
            'provider_reference' => $returnedOrderId,
            'payload' => $result,
        ];
    }

    public static function minorUnitAmount(float $amount, string $currency): int
    {
        if ($amount <= 0) {
            throw new DomainException('Payment amount must be greater than zero.');
        }

        $multiplier = in_array(strtolower($currency), self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100;
        return (int) round($amount * $multiplier);
    }

    public static function amountsMatch(float $actual, float $expected): bool
    {
        return abs($actual - $expected) < 0.005;
    }

    private function paypal(object $settings,bool $requireEnabled): PaypalGateway
    {
        if(($requireEnabled&&empty($settings->paypal_enabled))||empty($settings->paypal_client_id)||empty($settings->paypal_client_secret))throw new DomainException('PayPal is not configured for this school.');$cipher=new AdmissionCredentialCipher();return new PaypalGateway((string)$cipher->decrypt($settings->paypal_client_id),(string)$cipher->decrypt($settings->paypal_client_secret),(bool)$settings->paypal_sandbox);
    }
}

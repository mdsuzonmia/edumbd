<?php

namespace App\Models;

class InvoiceModel extends PaymentModel
{
    public function getInvoiceDetails($id)
    {
        return $this->getPaymentDetails($id);
    }

    public function invoiceNumber($payment)
    {
        $date = ! empty($payment->paid_at) ? strtotime($payment->paid_at) : strtotime($payment->created_at);

        return 'INV-' . date('Y', $date) . '-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}

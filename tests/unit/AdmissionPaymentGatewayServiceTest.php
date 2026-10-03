<?php

namespace Tests\Unit;

use App\Modules\admission\Services\AdmissionPaymentGatewayService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class AdmissionPaymentGatewayServiceTest extends TestCase
{
    public function testConvertsTwoDecimalCurrencyToMinorUnits(): void
    {
        self::assertSame(125050, AdmissionPaymentGatewayService::minorUnitAmount(1250.50, 'BDT'));
        self::assertSame(1099, AdmissionPaymentGatewayService::minorUnitAmount(10.99, 'usd'));
    }

    public function testConvertsZeroDecimalCurrencyToMinorUnits(): void
    {
        self::assertSame(1500, AdmissionPaymentGatewayService::minorUnitAmount(1500, 'JPY'));
    }

    public function testRejectsNonPositiveAmounts(): void
    {
        $this->expectException(DomainException::class);
        AdmissionPaymentGatewayService::minorUnitAmount(0, 'USD');
    }

    public function testAmountComparisonAllowsOnlyRoundingNoise(): void
    {
        self::assertTrue(AdmissionPaymentGatewayService::amountsMatch(10.001, 10.00));
        self::assertFalse(AdmissionPaymentGatewayService::amountsMatch(10.01, 10.00));
    }
}

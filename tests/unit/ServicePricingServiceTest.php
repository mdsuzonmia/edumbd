<?php
use App\Services\ServicePricingService;
use CodeIgniter\Test\CIUnitTestCase;
final class ServicePricingServiceTest extends CIUnitTestCase
{
    public function testResultPricingExamples():void
    {
        $service=new ServicePricingService();
        $this->assertSame(3750.0,$service->calculateAmount('PER_STUDENT_TIER',15,250));
        $this->assertSame(5400.0,$service->calculateAmount('PER_STUDENT_TIER',12,450));
        $this->assertSame(7000.0,$service->calculateAmount('PER_STUDENT_TIER',10,700));
    }
    public function testMinimumAndFixedPricing():void
    {
        $service=new ServicePricingService();
        $this->assertSame(1500.0,$service->calculateAmount('PER_STUDENT_TIER',15,20,true,1500));
        $this->assertSame(2000.0,$service->calculateAmount('FIXED_TIER',2000,450));
        $this->assertSame(0.0,$service->calculateAmount('FREE',999,450));
    }
}

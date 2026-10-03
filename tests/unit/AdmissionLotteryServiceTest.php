<?php

use App\Modules\admission\Services\LotteryService;
use CodeIgniter\Test\CIUnitTestCase;

final class AdmissionLotteryServiceTest extends CIUnitTestCase
{
    public function testSecureShufflePreservesEveryCandidateExactlyOnce(): void
    {
        $candidates=range(1,250);
        $shuffled=LotteryService::secureShuffle($candidates);
        sort($shuffled);
        $this->assertSame($candidates,$shuffled);
    }

    public function testPositionsAreClassifiedAtSeatBoundaries(): void
    {
        $this->assertSame(['result'=>'selected','waiting_position'=>null],LotteryService::classifyPosition(80,80,20));
        $this->assertSame(['result'=>'waiting','waiting_position'=>1],LotteryService::classifyPosition(81,80,20));
        $this->assertSame(['result'=>'waiting','waiting_position'=>20],LotteryService::classifyPosition(100,80,20));
        $this->assertSame(['result'=>'not_selected','waiting_position'=>null],LotteryService::classifyPosition(101,80,20));
    }
}

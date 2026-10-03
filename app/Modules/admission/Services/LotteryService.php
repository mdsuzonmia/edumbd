<?php

namespace App\Modules\admission\Services;

use App\Modules\admission\Models\AdmissionApplicationModel;
use App\Modules\admission\Models\AdmissionLotteryCandidateModel;
use App\Modules\admission\Models\AdmissionLotteryModel;
use App\Modules\admission\Models\AdmissionLotteryResultModel;
use App\Modules\admission\Models\AdmissionStatusHistoryModel;
use DomainException;
use Throwable;

final class LotteryService
{
    public const ALGORITHM_VERSION = 'secure-fisher-yates-v1';

    public function __construct(
        private readonly AdmissionLotteryModel $lotteries = new AdmissionLotteryModel(),
        private readonly AdmissionApplicationModel $applications = new AdmissionApplicationModel(),
        private readonly AdmissionLotteryCandidateModel $candidates = new AdmissionLotteryCandidateModel(),
        private readonly AdmissionLotteryResultModel $results = new AdmissionLotteryResultModel(),
        private readonly AdmissionStatusHistoryModel $history = new AdmissionStatusHistoryModel(),
    ) {}

    public function lock(int $lotteryId, int $schoolId, int $userId): object
    {
        $db = db_connect();
        $db->transBegin();
        try {
            $lottery = $this->lockedLottery($lotteryId, $schoolId);
            if (!$lottery || $lottery->status !== 'draft') {
                throw new DomainException('Only a draft lottery can be locked.');
            }

            $applications = $this->applications
                ->where('school_id', $schoolId)->where('circular_id', $lottery->circular_id)
                ->where('application_status', 'eligible')->orderBy('id', 'ASC')->findAll();
            if ($applications === []) {
                throw new DomainException('There are no eligible applications to lock.');
            }
            if ((int) $lottery->seat_count + (int) $lottery->waiting_count > count($applications)) {
                throw new DomainException('Seats plus waiting places cannot exceed the candidate count.');
            }

            $snapshotHashes = [];
            foreach ($applications as $application) {
                $line = implode('|', [$application->id, $application->application_no, $application->token, $application->student_name, $application->dob]);
                $snapshotHash = hash('sha256', $line);
                $snapshotHashes[] = $snapshotHash;
                $this->candidates->insert([
                    'school_id'=>$schoolId, 'lottery_id'=>$lotteryId, 'application_id'=>$application->id,
                    'application_no'=>$application->application_no, 'snapshot_hash'=>$snapshotHash,
                ]);
            }
            $hash = hash('sha256', implode("\n", $snapshotHashes));
            $now = date('Y-m-d H:i:s');
            $this->lotteries->update($lotteryId, [
                'candidate_count'=>count($applications), 'candidate_hash'=>$hash, 'status'=>'locked',
                'locked_at'=>$now, 'algorithm_version'=>self::ALGORITHM_VERSION, 'updated_by'=>$userId,
            ]);
            $db->transCommit();
            return $this->lotteries->find($lotteryId);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function draw(int $lotteryId, int $schoolId, int $userId): object
    {
        $db = db_connect();
        $db->transBegin();
        try {
            $lottery = $this->lockedLottery($lotteryId, $schoolId);
            if (!$lottery || $lottery->status !== 'locked') {
                throw new DomainException('The lottery must be locked and may only be drawn once.');
            }
            $candidates = $this->candidates->where('lottery_id', $lotteryId)->orderBy('id','ASC')->findAll();
            if (count($candidates) !== (int) $lottery->candidate_count) {
                throw new DomainException('The frozen candidate snapshot is incomplete.');
            }
            // Validate the aggregate against the individual immutable snapshot hashes.
            $snapshotHashes = [];
            foreach ($candidates as $candidate) {
                if (!preg_match('/^[a-f0-9]{64}$/', (string) $candidate->snapshot_hash)) {
                    throw new DomainException('The candidate snapshot failed its integrity check.');
                }
                $snapshotHashes[] = $candidate->snapshot_hash;
            }
            if (!hash_equals((string)$lottery->candidate_hash, hash('sha256', implode("\n", $snapshotHashes)))) {
                throw new DomainException('The frozen candidate list has changed since it was locked.');
            }

            $order = self::secureShuffle($candidates);

            $resultLines = [];
            foreach ($order as $index => $candidate) {
                $position = $index + 1;
                ['result'=>$result,'waiting_position'=>$waitingPosition] = self::classifyPosition($position,(int)$lottery->seat_count,(int)$lottery->waiting_count);
                $applicationStatus = $result;
                $this->results->insert(['school_id'=>$schoolId,'lottery_id'=>$lotteryId,'application_id'=>$candidate->application_id,'draw_position'=>$position,'result'=>$result,'waiting_position'=>$waitingPosition]);
                $this->applications->update($candidate->application_id, ['application_status'=>$applicationStatus,'updated_by'=>$userId]);
                $this->history->insert(['school_id'=>$schoolId,'application_id'=>$candidate->application_id,'from_status'=>'eligible','to_status'=>$applicationStatus,'note'=>'Lottery draw #'.$lotteryId,'created_by'=>$userId]);
                $resultLines[] = implode('|', [$position,$candidate->application_id,$result,$waitingPosition ?? '']);
            }
            $this->lotteries->update($lotteryId, ['status'=>'drawn','result_hash'=>hash('sha256',implode("\n",$resultLines)),'drawn_at'=>date('Y-m-d H:i:s'),'updated_by'=>$userId]);
            $db->transCommit();
            return $this->lotteries->find($lotteryId);
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function lockedLottery(int $id, int $schoolId): ?object
    {
        $prefix = db_connect()->getPrefix();
        return db_connect()->query("SELECT * FROM {$prefix}admission_lotteries WHERE id = ? AND school_id = ? FOR UPDATE", [$id,$schoolId])->getRow();
    }

    public static function secureShuffle(array $items): array
    {
        $items=array_values($items);
        for ($i=count($items)-1;$i>0;--$i) {
            $j=random_int(0,$i);
            [$items[$i],$items[$j]]=[$items[$j],$items[$i]];
        }
        return $items;
    }

    public static function classifyPosition(int $position,int $seatCount,int $waitingCount): array
    {
        if ($position<1||$seatCount<0||$waitingCount<0) throw new DomainException('Invalid lottery position or allocation.');
        if ($position<=$seatCount) return ['result'=>'selected','waiting_position'=>null];
        if ($position<=$seatCount+$waitingCount) return ['result'=>'waiting','waiting_position'=>$position-$seatCount];
        return ['result'=>'not_selected','waiting_position'=>null];
    }

    public function verifyAudit(object $lottery): array
    {
        $candidates=$this->candidates->where('lottery_id',$lottery->id)->orderBy('id','ASC')->findAll();
        $candidateHash=hash('sha256',implode("\n",array_map(static fn($c)=>(string)$c->snapshot_hash,$candidates)));
        $candidateValid=count($candidates)===(int)$lottery->candidate_count
            && is_string($lottery->candidate_hash) && hash_equals($lottery->candidate_hash,$candidateHash);
        $resultValid=null;
        if (!empty($lottery->result_hash)) {
            $results=$this->results->where('lottery_id',$lottery->id)->orderBy('draw_position','ASC')->findAll();
            $lines=array_map(static fn($r)=>implode('|',[$r->draw_position,$r->application_id,$r->result,$r->waiting_position??'']),$results);
            $resultValid=count($results)===(int)$lottery->candidate_count
                && hash_equals((string)$lottery->result_hash,hash('sha256',implode("\n",$lines)));
        }
        return ['candidate_valid'=>$candidateValid,'result_valid'=>$resultValid];
    }
}

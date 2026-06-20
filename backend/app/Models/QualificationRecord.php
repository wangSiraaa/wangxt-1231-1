<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualificationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'resident_id',
        'id_card',
        'name',
        'phone',
        'result',
        'reason',
        'review_type',
        'income_amount',
        'asset_amount',
        'house_area',
        'family_member_count',
        'remark',
        'affects_renewal',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'income_amount' => 'decimal:2',
        'asset_amount' => 'decimal:2',
        'house_area' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(QualificationBatch::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePass($query)
    {
        return $query->where('result', 1);
    }

    public function scopeFail($query)
    {
        return $query->where('result', 2);
    }

    public function scopePending($query)
    {
        return $query->where('result', 3);
    }

    public function scopeByIdCard($query, $idCard)
    {
        return $query->where('id_card', $idCard);
    }

    public function canReview()
    {
        return $this->result === 3;
    }

    public function pass($userId, $remark = null)
    {
        if (!$this->canReview()) {
            throw new \Exception('该记录已复核');
        }

        $this->result = 1;
        $this->reviewed_by = $userId;
        $this->reviewed_at = now();
        if ($remark) {
            $this->remark = $remark;
        }
        $this->save();

        $this->batch->updateCounts();

        $this->updateLeaseRenewalStatus();

        return $this;
    }

    public function fail($userId, $reason, $remark = null)
    {
        if (!$this->canReview()) {
            throw new \Exception('该记录已复核');
        }

        $this->result = 2;
        $this->reason = $reason;
        $this->reviewed_by = $userId;
        $this->reviewed_at = now();
        if ($remark) {
            $this->remark = $remark;
        }
        $this->save();

        $this->batch->updateCounts();

        $this->updateLeaseRenewalStatus();

        return $this;
    }

    protected function updateLeaseRenewalStatus()
    {
        if (!$this->resident || !$this->affects_renewal) {
            return;
        }

        $activeLease = $this->resident->activeLease;
        if ($activeLease) {
            if ($this->result === 1) {
                $activeLease->renewal_status = 1;
            } elseif ($this->result === 2) {
                $activeLease->renewal_status = 0;
            }
            $activeLease->save();
        }
    }

    public function getResultTextAttribute()
    {
        $results = [
            1 => '通过',
            2 => '不通过',
            3 => '待复核',
        ];
        return $results[$this->result] ?? '未知';
    }
}

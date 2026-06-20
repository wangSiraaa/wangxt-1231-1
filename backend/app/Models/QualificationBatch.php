<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualificationBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'batch_no',
        'batch_name',
        'source',
        'review_date',
        'valid_from',
        'valid_to',
        'total_count',
        'pass_count',
        'fail_count',
        'pending_count',
        'status',
        'remark',
        'import_file',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function records()
    {
        return $this->hasMany(QualificationRecord::class);
    }

    public function passRecords()
    {
        return $this->hasMany(QualificationRecord::class)->where('result', 1);
    }

    public function failRecords()
    {
        return $this->hasMany(QualificationRecord::class)->where('result', 2);
    }

    public function pendingRecords()
    {
        return $this->hasMany(QualificationRecord::class)->where('result', 3);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 1);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 2);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 3);
    }

    public function canPublish()
    {
        return $this->status === 1;
    }

    public function canComplete()
    {
        return $this->status === 2 && $this->pending_count === 0;
    }

    public function publish()
    {
        if (!$this->canPublish()) {
            throw new \Exception('批次状态不允许发布');
        }

        $this->status = 2;
        $this->save();

        return $this;
    }

    public function complete()
    {
        if (!$this->canComplete()) {
            throw new \Exception('还有待复核记录，无法完成');
        }

        $this->status = 3;
        $this->save();

        return $this;
    }

    public function updateCounts()
    {
        $this->total_count = $this->records()->count();
        $this->pass_count = $this->passRecords()->count();
        $this->fail_count = $this->failRecords()->count();
        $this->pending_count = $this->pendingRecords()->count();
        $this->save();

        return $this;
    }

    public static function generateBatchNo()
    {
        return 'QB' . date('Ymd') . rand(1000, 9999);
    }
}

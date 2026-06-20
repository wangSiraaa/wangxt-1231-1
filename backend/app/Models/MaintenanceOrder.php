<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_no',
        'resident_id',
        'lease_id',
        'type',
        'category',
        'title',
        'description',
        'contact_person',
        'contact_phone',
        'appointment_time',
        'actual_start_time',
        'actual_end_time',
        'assigned_to',
        'team_id',
        'material_cost',
        'labor_cost',
        'total_cost',
        'status',
        'urgency_level',
        'repair_result',
        'cancel_reason',
        'has_photos',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'appointment_time' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos()
    {
        return $this->hasMany(MaintenancePhoto::class);
    }

    public function materials()
    {
        return $this->hasMany(MaintenanceMaterial::class);
    }

    public function timelines()
    {
        return $this->hasMany(MaintenanceTimeline::class)->orderBy('created_at');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUrgent($query)
    {
        return $query->where('urgency_level', '>=', 2);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [1, 2]);
    }

    public function canBeCompleted()
    {
        return $this->status === 3;
    }

    public function canBeAccepted()
    {
        return $this->status === 1;
    }

    public function canBeStarted()
    {
        return $this->status === 2;
    }

    public function canAddPhotos()
    {
        return in_array($this->status, [3, 4]);
    }

    public function canClose()
    {
        if ($this->status !== 4) {
            return false;
        }
        return $this->has_photos || $this->photos()->where('photo_type', 'completion')->count() > 0;
    }

    public function accept($userId, $userName)
    {
        if (!$this->canBeAccepted()) {
            throw new \Exception('工单状态不允许接单');
        }

        $this->status = 2;
        $this->assigned_to = $userId;
        $this->save();

        $this->addTimeline(2, '接单', $userId, $userName);

        return $this;
    }

    public function start($userId, $userName)
    {
        if (!$this->canBeStarted()) {
            throw new \Exception('工单状态不允许开始维修');
        }

        $this->status = 3;
        $this->actual_start_time = now();
        $this->save();

        $this->addTimeline(3, '开始维修', $userId, $userName);

        return $this;
    }

    public function complete($userId, $userName, $result, $materialCost = 0, $laborCost = 0)
    {
        if (!$this->canBeCompleted()) {
            throw new \Exception('工单状态不允许完工');
        }

        $this->status = 4;
        $this->actual_end_time = now();
        $this->repair_result = $result;
        $this->material_cost = $materialCost;
        $this->labor_cost = $laborCost;
        $this->total_cost = $materialCost + $laborCost;
        $this->save();

        $this->addTimeline(4, '维修完工', $userId, $userName);

        return $this;
    }

    public function close($userId, $userName)
    {
        if (!$this->canClose()) {
            throw new \Exception('请先上传完工照片后再结案');
        }

        $this->status = 5;
        $this->save();

        $this->addTimeline(5, '验收结案', $userId, $userName);

        return $this;
    }

    public function cancel($userId, $userName, $reason)
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('工单状态不允许取消');
        }

        $this->status = 6;
        $this->cancel_reason = $reason;
        $this->save();

        $this->addTimeline(6, '取消工单: ' . $reason, $userId, $userName);

        return $this;
    }

    public function assign($userId, $userName, $assigneeId)
    {
        $this->assigned_to = $assigneeId;
        $this->save();

        $this->addTimeline(1, '派单', $userId, $userName);

        return $this;
    }

    protected function addTimeline($actionType, $content, $operatorId, $operatorName)
    {
        $this->timelines()->create([
            'action_type' => $actionType,
            'action_content' => $content,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
        ]);
    }

    public static function generateOrderNo()
    {
        return 'WO' . date('YmdHis') . rand(1000, 9999);
    }
}

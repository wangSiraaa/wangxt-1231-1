<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceTimeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_order_id',
        'action_type',
        'action_content',
        'operator_id',
        'operator_name',
    ];

    public function maintenanceOrder()
    {
        return $this->belongsTo(MaintenanceOrder::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function getActionTypeTextAttribute()
    {
        $types = [
            1 => '派单',
            2 => '接单',
            3 => '开始维修',
            4 => '维修完工',
            5 => '验收结案',
            6 => '取消工单',
        ];
        return $types[$this->action_type] ?? '未知';
    }
}

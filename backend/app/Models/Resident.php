<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id_card',
        'name',
        'phone',
        'gender',
        'birth_date',
        'address',
        'building',
        'unit',
        'room',
        'house_area',
        'status',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function familyMembers()
    {
        return $this->hasMany(ResidentFamilyMember::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease()
    {
        return $this->hasOne(Lease::class)->where('status', 1)->latest('start_date');
    }

    public function arrears()
    {
        return $this->hasMany(Arrear::class);
    }

    public function unpaidArrears()
    {
        return $this->hasMany(Arrear::class)->whereIn('status', [1, 2, 4]);
    }

    public function totalUnpaidAmount()
    {
        return $this->unpaidArrears()->sum('unpaid_amount');
    }

    public function maintenanceOrders()
    {
        return $this->hasMany(MaintenanceOrder::class);
    }

    public function qualificationRecords()
    {
        return $this->hasMany(QualificationRecord::class);
    }

    public function latestQualificationRecord()
    {
        return $this->hasOne(QualificationRecord::class)->latest('reviewed_at');
    }

    public function canRegisterEmergencyMaintenance()
    {
        $threshold = config('system.arrears_threshold', 10000);
        return $this->totalUnpaidAmount() > $threshold;
    }

    public function canRenewLease()
    {
        $latestRecord = $this->latestQualificationRecord;
        if (!$latestRecord) {
            return false;
        }
        return $latestRecord->result === 1;
    }

    public function getFullAddressAttribute()
    {
        $parts = array_filter([$this->building, $this->unit, $this->room]);
        return implode('-', $parts);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('id_card', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('building', 'like', "%{$keyword}%")
                ->orWhere('room', 'like', "%{$keyword}%");
        });
    }
}

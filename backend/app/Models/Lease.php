<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lease extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lease_no',
        'resident_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'deposit',
        'area',
        'lease_type',
        'status',
        'renewal_status',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function arrears()
    {
        return $this->hasMany(Arrear::class);
    }

    public function renewalApplications()
    {
        return $this->hasMany(LeaseRenewalApplication::class);
    }

    public function maintenanceOrders()
    {
        return $this->hasMany(MaintenanceOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeExpiring($query, $days = 90)
    {
        return $query->where('status', 1)
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 1)
            ->where('end_date', '<', now());
    }

    public function isExpiring($days = 90)
    {
        return $this->status === 1 && $this->end_date <= now()->addDays($days);
    }

    public function isOverdue()
    {
        return $this->status === 1 && $this->end_date < now();
    }

    public function canApplyRenewal()
    {
        if ($this->status !== 1) {
            return false;
        }
        if (!$this->resident->canRenewLease()) {
            return false;
        }
        return $this->isExpiring(90);
    }

    public function calculateTotalArrears()
    {
        return $this->arrears()
            ->whereIn('status', [1, 2, 4])
            ->sum('unpaid_amount');
    }
}

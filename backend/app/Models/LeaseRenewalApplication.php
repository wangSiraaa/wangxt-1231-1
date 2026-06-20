<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaseRenewalApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'resident_id',
        'apply_date',
        'new_start_date',
        'new_end_date',
        'new_monthly_rent',
        'qualification_result',
        'status',
        'audit_opinion',
        'audited_by',
        'audited_at',
        'created_by',
    ];

    protected $casts = [
        'apply_date' => 'date',
        'new_start_date' => 'date',
        'new_end_date' => 'date',
        'audited_at' => 'datetime',
    ];

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 1);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 2);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 3);
    }

    public function canAudit()
    {
        return $this->status === 1;
    }

    public function approve($userId, $opinion = null)
    {
        $this->status = 2;
        $this->audited_by = $userId;
        $this->audited_at = now();
        $this->audit_opinion = $opinion;
        $this->save();

        $this->lease->renewal_status = 3;
        $this->lease->save();

        return $this;
    }

    public function reject($userId, $opinion)
    {
        $this->status = 3;
        $this->audited_by = $userId;
        $this->audited_at = now();
        $this->audit_opinion = $opinion;
        $this->save();

        $this->lease->renewal_status = 0;
        $this->lease->save();

        return $this;
    }
}

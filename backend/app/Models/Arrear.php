<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arrear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bill_no',
        'resident_id',
        'lease_id',
        'bill_period',
        'bill_date',
        'due_date',
        'rent_amount',
        'property_fee',
        'water_fee',
        'electric_fee',
        'gas_fee',
        'other_fee',
        'total_amount',
        'paid_amount',
        'unpaid_amount',
        'late_fee',
        'status',
        'payment_date',
        'payment_method',
        'remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function paymentRecords()
    {
        return $this->hasMany(PaymentRecord::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [1, 2, 4]);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 3);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 4)
            ->orWhere(function ($q) {
                $q->whereIn('status', [1, 2])
                    ->where('due_date', '<', now());
            });
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('bill_period', $period);
    }

    public function isOverdue()
    {
        if ($this->status === 4) {
            return true;
        }
        return in_array($this->status, [1, 2]) && $this->due_date < now()->toDateString();
    }

    public function calculateLateFee()
    {
        if (!$this->isOverdue() || $this->unpaid_amount <= 0) {
            return 0;
        }

        $daysOverdue = now()->diffInDays($this->due_date);
        $rate = 0.0005;

        return round($this->unpaid_amount * $rate * $daysOverdue, 2);
    }

    public function recordPayment($amount, $method, $transactionNo = null, $remark = null, $userId = null)
    {
        $payment = new PaymentRecord([
            'payment_no' => 'PAY' . date('YmdHis') . rand(1000, 9999),
            'arrear_id' => $this->id,
            'resident_id' => $this->resident_id,
            'amount' => $amount,
            'payment_method' => $method,
            'transaction_no' => $transactionNo,
            'payment_date' => now(),
            'remark' => $remark,
            'created_by' => $userId,
        ]);
        $payment->save();

        $this->paid_amount += $amount;
        $this->unpaid_amount = max(0, $this->total_amount + $this->late_fee - $this->paid_amount);

        if ($this->unpaid_amount <= 0) {
            $this->status = 3;
            $this->payment_date = now();
        } elseif ($this->paid_amount > 0) {
            $this->status = 2;
        }

        $this->save();

        return $payment;
    }

    public static function generateBillNo()
    {
        return 'BILL' . date('Ymd') . rand(10000, 99999);
    }
}

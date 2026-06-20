<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_no',
        'arrear_id',
        'resident_id',
        'amount',
        'payment_method',
        'transaction_no',
        'payment_date',
        'remark',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function arrear()
    {
        return $this->belongsTo(Arrear::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_order_id',
        'material_name',
        'specification',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'remark',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function maintenanceOrder()
    {
        return $this->belongsTo(MaintenanceOrder::class);
    }

    protected static function booted()
    {
        static::creating(function ($material) {
            if (empty($material->amount)) {
                $material->amount = $material->quantity * $material->unit_price;
            }
        });

        static::updating(function ($material) {
            $material->amount = $material->quantity * $material->unit_price;
        });
    }
}

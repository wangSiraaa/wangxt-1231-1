<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenancePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_order_id',
        'photo_type',
        'file_path',
        'file_name',
        'file_size',
        'description',
        'uploaded_by',
    ];

    public function maintenanceOrder()
    {
        return $this->belongsTo(MaintenanceOrder::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeType($query, $type)
    {
        return $query->where('photo_type', $type);
    }

    public function getFullUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}

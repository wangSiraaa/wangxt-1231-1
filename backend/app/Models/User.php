<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'department',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function assignedMaintenanceOrders()
    {
        return $this->hasMany(MaintenanceOrder::class, 'assigned_to');
    }

    public function createdMaintenanceOrders()
    {
        return $this->hasMany(MaintenanceOrder::class, 'created_by');
    }

    public function maintenanceTimelines()
    {
        return $this->hasMany(MaintenanceTimeline::class, 'operator_id');
    }
}

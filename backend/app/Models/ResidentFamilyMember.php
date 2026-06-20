<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentFamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_id',
        'id_card',
        'name',
        'relation',
        'gender',
        'birth_date',
        'phone',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }
}

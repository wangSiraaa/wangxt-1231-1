<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_key',
        'config_value',
        'config_type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public static function getValue($key, $default = null)
    {
        $config = self::where('config_key', $key)->first();
        if (!$config) {
            return $default;
        }

        return self::castValue($config->config_value, $config->config_type);
    }

    public static function setValue($key, $value, $type = 'string', $description = null, $isPublic = true)
    {
        return self::updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => $value,
                'config_type' => $type,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );
    }

    public static function getAllPublic()
    {
        return self::where('is_public', true)
            ->get()
            ->mapWithKeys(function ($config) {
                return [$config->config_key => self::castValue($config->config_value, $config->config_type)];
            });
    }

    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'float':
            case 'decimal':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'array':
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    public function getCastedValueAttribute()
    {
        return self::castValue($this->config_value, $this->config_type);
    }
}

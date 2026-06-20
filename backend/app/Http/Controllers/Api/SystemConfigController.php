<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemConfig::query();

        if ($request->has('is_public') && !is_null($request->is_public)) {
            $query->where('is_public', $request->is_public);
        }

        $configs = $query->get()->map(function ($config) {
            return [
                'id' => $config->id,
                'config_key' => $config->config_key,
                'config_value' => $config->casted_value,
                'config_type' => $config->config_type,
                'description' => $config->description,
                'is_public' => $config->is_public,
                'created_at' => $config->created_at,
                'updated_at' => $config->updated_at,
            ];
        });

        return response()->json($configs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'config_key' => 'required|string|max:100|unique:system_configs,config_key',
            'config_value' => 'required',
            'config_type' => 'required|in:string,integer,float,decimal,boolean,array,json',
            'description' => 'nullable|string|max:200',
            'is_public' => 'nullable|boolean',
        ]);

        $value = $validated['config_value'];
        if (in_array($validated['config_type'], ['array', 'json'])) {
            $value = json_encode($value);
        }

        $config = SystemConfig::create([
            'config_key' => $validated['config_key'],
            'config_value' => $value,
            'config_type' => $validated['config_type'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? true,
        ]);

        return response()->json([
            'id' => $config->id,
            'config_key' => $config->config_key,
            'config_value' => $config->casted_value,
            'config_type' => $config->config_type,
            'description' => $config->description,
            'is_public' => $config->is_public,
        ], 201);
    }

    public function public()
    {
        $configs = SystemConfig::getAllPublic();

        return response()->json($configs);
    }

    public function update(Request $request, SystemConfig $config)
    {
        $validated = $request->validate([
            'config_value' => 'required',
            'config_type' => 'required|in:string,integer,float,decimal,boolean,array,json',
            'description' => 'nullable|string|max:200',
            'is_public' => 'nullable|boolean',
        ]);

        $value = $validated['config_value'];
        if (in_array($validated['config_type'], ['array', 'json'])) {
            $value = json_encode($value);
        }

        $config->update([
            'config_value' => $value,
            'config_type' => $validated['config_type'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? true,
        ]);

        return response()->json([
            'id' => $config->id,
            'config_key' => $config->config_key,
            'config_value' => $config->casted_value,
            'config_type' => $config->config_type,
            'description' => $config->description,
            'is_public' => $config->is_public,
        ]);
    }

    public function destroy(SystemConfig $config)
    {
        $config->delete();
        return response()->json(['message' => '删除成功']);
    }
}

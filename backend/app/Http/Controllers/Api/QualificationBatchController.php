<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QualificationBatch;
use App\Models\QualificationRecord;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QualificationBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = QualificationBatch::with('creator');

        if ($request->has('status') && $request->status) {
            $query->status($request->status);
        }

        if ($request->has('keyword') && $request->keyword) {
            $query->where(function ($q) use ($request) {
                $q->where('batch_name', 'like', "%{$request->keyword}%")
                    ->orWhere('batch_no', 'like', "%{$request->keyword}%");
            });
        }

        $batches = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json($batches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:200',
            'review_date' => 'required|date',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'remark' => 'nullable|string',
        ]);

        $validated['batch_no'] = QualificationBatch::generateBatchNo();
        $validated['source'] = 'manual';
        $validated['status'] = 1;
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $batch = QualificationBatch::create($validated);

        return response()->json($batch->load('creator'), 201);
    }

    public function show(QualificationBatch $batch)
    {
        $batch->load(['creator']);
        $batch->can_publish = $batch->canPublish();
        $batch->can_complete = $batch->canComplete();

        return response()->json($batch);
    }

    public function update(Request $request, QualificationBatch $batch)
    {
        if ($batch->status !== 1) {
            return response()->json(['message' => '只有草稿状态的批次可以修改'], 400);
        }

        $validated = $request->validate([
            'batch_name' => 'required|string|max:200',
            'review_date' => 'required|date',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'remark' => 'nullable|string',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $batch->update($validated);

        return response()->json($batch->load('creator'));
    }

    public function destroy(QualificationBatch $batch)
    {
        if ($batch->status !== 1) {
            return response()->json(['message' => '只有草稿状态的批次可以删除'], 400);
        }

        $batch->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function publish(Request $request, QualificationBatch $batch)
    {
        try {
            $batch->publish();
            return response()->json([
                'message' => '批次已发布',
                'batch' => $batch->fresh('creator'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function complete(Request $request, QualificationBatch $batch)
    {
        try {
            $batch->complete();
            return response()->json([
                'message' => '批次已完成',
                'batch' => $batch->fresh('creator'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'batch_name' => 'required|string|max:200',
            'review_date' => 'required|date',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'records' => 'required|array',
        ]);

        $records = $validated['records'];

        $validator = Validator::make($records, [
            '*.id_card' => 'required|size:18',
            '*.name' => 'required|string|max:50',
            '*.result' => 'required|in:1,2,3',
            '*.phone' => 'nullable|string|max:20',
            '*.reason' => 'nullable|string|max:500',
            '*.review_type' => 'nullable|string|max:50',
            '*.income_amount' => 'nullable|numeric|min:0',
            '*.asset_amount' => 'nullable|numeric|min:0',
            '*.house_area' => 'nullable|numeric|min:0',
            '*.family_member_count' => 'nullable|integer|min:0',
            '*.affects_renewal' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $batch = QualificationBatch::create([
            'batch_no' => QualificationBatch::generateBatchNo(),
            'batch_name' => $validated['batch_name'],
            'source' => 'manual',
            'review_date' => $validated['review_date'],
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_to' => $validated['valid_to'] ?? null,
            'status' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $processedCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($records as $index => $recordData) {
            try {
                $resident = Resident::where('id_card', $recordData['id_card'])->first();

                QualificationRecord::create([
                    'batch_id' => $batch->id,
                    'resident_id' => $resident?->id,
                    'id_card' => $recordData['id_card'],
                    'name' => $recordData['name'],
                    'phone' => $recordData['phone'] ?? null,
                    'result' => $recordData['result'],
                    'reason' => $recordData['reason'] ?? null,
                    'review_type' => $recordData['review_type'] ?? null,
                    'income_amount' => $recordData['income_amount'] ?? null,
                    'asset_amount' => $recordData['asset_amount'] ?? null,
                    'house_area' => $recordData['house_area'] ?? null,
                    'family_member_count' => $recordData['family_member_count'] ?? null,
                    'affects_renewal' => $recordData['affects_renewal'] ?? true,
                    'reviewed_by' => $recordData['result'] !== 3 ? $request->user()->id : null,
                    'reviewed_at' => $recordData['result'] !== 3 ? now() : null,
                ]);

                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = [
                    'row' => $index + 1,
                    'id_card' => $recordData['id_card'],
                    'name' => $recordData['name'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        $batch->updateCounts();

        return response()->json([
            'message' => '导入完成',
            'batch' => $batch->load('creator'),
            'processed_count' => $processedCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ], 201);
    }

    public function records(Request $request, QualificationBatch $batch)
    {
        $query = $batch->records()->with('resident');

        if ($request->has('result') && $request->result) {
            $query->where('result', $request->result);
        }

        if ($request->has('keyword') && $request->keyword) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('id_card', 'like', "%{$request->keyword}%");
            });
        }

        $records = $query->paginate($request->per_page ?? 20);

        return response()->json($records);
    }
}

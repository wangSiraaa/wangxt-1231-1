<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QualificationRecord;
use App\Models\Resident;
use Illuminate\Http\Request;

class QualificationRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = QualificationRecord::with(['batch', 'resident', 'reviewer']);

        if ($request->has('result') && $request->result) {
            if ($request->result === 'pass') {
                $query->pass();
            } elseif ($request->result === 'fail') {
                $query->fail();
            } elseif ($request->result === 'pending') {
                $query->pending();
            } else {
                $query->where('result', $request->result);
            }
        }

        if ($request->has('batch_id') && $request->batch_id) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('resident_id') && $request->resident_id) {
            $query->where('resident_id', $request->resident_id);
        }

        if ($request->has('id_card') && $request->id_card) {
            $query->byIdCard($request->id_card);
        }

        if ($request->has('keyword') && $request->keyword) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('id_card', 'like', "%{$request->keyword}%");
            });
        }

        $records = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:qualification_batches,id',
            'id_card' => 'required|size:18',
            'name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'result' => 'required|in:1,2,3',
            'reason' => 'nullable|string|max:500',
            'review_type' => 'nullable|string|max:50',
            'income_amount' => 'nullable|numeric|min:0',
            'asset_amount' => 'nullable|numeric|min:0',
            'house_area' => 'nullable|numeric|min:0',
            'family_member_count' => 'nullable|integer|min:0',
            'affects_renewal' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        $batch = \App\Models\QualificationBatch::findOrFail($validated['batch_id']);
        if ($batch->status !== 1) {
            return response()->json(['message' => '只有草稿状态的批次可以添加记录'], 400);
        }

        $resident = Resident::where('id_card', $validated['id_card'])->first();

        $record = QualificationRecord::create([
            'batch_id' => $validated['batch_id'],
            'resident_id' => $resident?->id,
            'id_card' => $validated['id_card'],
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'result' => $validated['result'],
            'reason' => $validated['reason'] ?? null,
            'review_type' => $validated['review_type'] ?? null,
            'income_amount' => $validated['income_amount'] ?? null,
            'asset_amount' => $validated['asset_amount'] ?? null,
            'house_area' => $validated['house_area'] ?? null,
            'family_member_count' => $validated['family_member_count'] ?? null,
            'affects_renewal' => $validated['affects_renewal'] ?? true,
            'remark' => $validated['remark'] ?? null,
            'reviewed_by' => $validated['result'] !== 3 ? $request->user()->id : null,
            'reviewed_at' => $validated['result'] !== 3 ? now() : null,
        ]);

        $batch->updateCounts();

        return response()->json($record->load(['batch', 'resident', 'reviewer']), 201);
    }

    public function show(QualificationRecord $record)
    {
        $record->load(['batch', 'resident', 'reviewer']);
        $record->can_review = $record->canReview();

        return response()->json($record);
    }

    public function update(Request $request, QualificationRecord $record)
    {
        if ($record->batch->status !== 1) {
            return response()->json(['message' => '只有草稿状态的批次可以修改记录'], 400);
        }

        $validated = $request->validate([
            'id_card' => 'required|size:18',
            'name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'result' => 'required|in:1,2,3',
            'reason' => 'nullable|string|max:500',
            'review_type' => 'nullable|string|max:50',
            'income_amount' => 'nullable|numeric|min:0',
            'asset_amount' => 'nullable|numeric|min:0',
            'house_area' => 'nullable|numeric|min:0',
            'family_member_count' => 'nullable|integer|min:0',
            'affects_renewal' => 'nullable|boolean',
            'remark' => 'nullable|string',
        ]);

        $resident = Resident::where('id_card', $validated['id_card'])->first();
        $validated['resident_id'] = $resident?->id;

        $wasPending = $record->result === 3;
        $record->update($validated);

        if ($wasPending && $validated['result'] !== 3) {
            $record->reviewed_by = $request->user()->id;
            $record->reviewed_at = now();
            $record->save();
        }

        $record->batch->updateCounts();

        return response()->json($record->load(['batch', 'resident', 'reviewer']));
    }

    public function destroy(QualificationRecord $record)
    {
        if ($record->batch->status !== 1) {
            return response()->json(['message' => '只有草稿状态的批次可以删除记录'], 400);
        }

        $batch = $record->batch;
        $record->delete();
        $batch->updateCounts();

        return response()->json(['message' => '删除成功']);
    }

    public function pass(Request $request, QualificationRecord $record)
    {
        try {
            $validated = $request->validate([
                'remark' => 'nullable|string',
            ]);

            $record->pass($request->user()->id, $validated['remark'] ?? null);

            return response()->json([
                'message' => '复核通过',
                'record' => $record->fresh(['batch', 'resident', 'reviewer']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function fail(Request $request, QualificationRecord $record)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
                'remark' => 'nullable|string',
            ]);

            $record->fail($request->user()->id, $validated['reason'], $validated['remark'] ?? null);

            return response()->json([
                'message' => '复核不通过',
                'record' => $record->fresh(['batch', 'resident', 'reviewer']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

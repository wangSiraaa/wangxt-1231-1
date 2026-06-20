<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaseRenewalApplication;
use App\Models\Lease;
use Illuminate\Http\Request;

class LeaseRenewalController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lease_id' => 'required|exists:leases,id',
            'new_start_date' => 'required|date|after_or_equal:today',
            'new_end_date' => 'required|date|after:new_start_date',
            'new_monthly_rent' => 'required|numeric|min:0',
        ]);

        $lease = Lease::findOrFail($validated['lease_id']);

        if (!$lease->canApplyRenewal()) {
            return response()->json(['message' => '该租约不符合续租条件，资格复核未通过或租约未到期'], 400);
        }

        if ($lease->renewal_status === 2) {
            return response()->json(['message' => '该租约已有续租申请待审核'], 400);
        }

        $application = LeaseRenewalApplication::create([
            'lease_id' => $validated['lease_id'],
            'resident_id' => $lease->resident_id,
            'apply_date' => now(),
            'new_start_date' => $validated['new_start_date'],
            'new_end_date' => $validated['new_end_date'],
            'new_monthly_rent' => $validated['new_monthly_rent'],
            'qualification_result' => $lease->resident->canRenewLease() ? 1 : 2,
            'status' => 1,
            'created_by' => $request->user()->id,
        ]);

        $lease->renewal_status = 2;
        $lease->save();

        return response()->json($application->load(['lease', 'resident']), 201);
    }

    public function pending(Request $request)
    {
        $query = LeaseRenewalApplication::with(['lease', 'resident', 'creator'])
            ->where('status', 1);

        $applications = $query->orderBy('apply_date', 'desc')->paginate($request->per_page ?? 20);

        return response()->json($applications);
    }

    public function approve(Request $request, LeaseRenewalApplication $application)
    {
        if (!$application->canAudit()) {
            return response()->json(['message' => '该申请已审核'], 400);
        }

        $validated = $request->validate([
            'audit_opinion' => 'nullable|string',
        ]);

        $application->approve($request->user()->id, $validated['audit_opinion'] ?? null);

        Lease::create([
            'lease_no' => 'L' . date('Ymd') . rand(10000, 99999),
            'resident_id' => $application->resident_id,
            'start_date' => $application->new_start_date,
            'end_date' => $application->new_end_date,
            'monthly_rent' => $application->new_monthly_rent,
            'deposit' => $application->lease->deposit,
            'area' => $application->lease->area,
            'lease_type' => $application->lease->lease_type,
            'status' => 1,
            'renewal_status' => 0,
            'remark' => '续租生成',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => '续租申请已通过',
            'application' => $application->load(['lease', 'resident', 'auditor']),
        ]);
    }

    public function reject(Request $request, LeaseRenewalApplication $application)
    {
        if (!$application->canAudit()) {
            return response()->json(['message' => '该申请已审核'], 400);
        }

        $validated = $request->validate([
            'audit_opinion' => 'required|string',
        ]);

        $application->reject($request->user()->id, $validated['audit_opinion']);

        return response()->json([
            'message' => '续租申请已驳回',
            'application' => $application->load(['lease', 'resident', 'auditor']),
        ]);
    }
}

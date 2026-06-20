<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Arrear;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Lease::with('resident');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('resident_id') && $request->resident_id) {
            $query->where('resident_id', $request->resident_id);
        }

        if ($request->has('keyword') && $request->keyword) {
            $query->whereHas('resident', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('id_card', 'like', "%{$request->keyword}%");
            });
        }

        if ($request->has('expiring') && $request->expiring) {
            $days = $request->expiring_days ?? 90;
            $query->expiring($days);
        }

        $leases = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        $leases->getCollection()->transform(function ($lease) {
            $lease->total_arrears = $lease->calculateTotalArrears();
            $lease->is_expiring = $lease->isExpiring();
            $lease->is_overdue = $lease->isOverdue();
            $lease->can_renew = $lease->canApplyRenewal();
            return $lease;
        });

        return response()->json($leases);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lease_no' => 'required|unique:leases,lease_no|max:50',
            'resident_id' => 'required|exists:residents,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'area' => 'required|numeric|min:0',
            'lease_type' => 'required|in:1,2',
            'status' => 'nullable|in:1,2,3',
            'remark' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $lease = Lease::create($validated);

        return response()->json($lease->load('resident'), 201);
    }

    public function show(Lease $lease)
    {
        $lease->load(['resident', 'arrears', 'renewalApplications']);
        $lease->total_arrears = $lease->calculateTotalArrears();
        $lease->is_expiring = $lease->isExpiring();
        $lease->is_overdue = $lease->isOverdue();
        $lease->can_renew = $lease->canApplyRenewal();

        return response()->json($lease);
    }

    public function update(Request $request, Lease $lease)
    {
        $validated = $request->validate([
            'lease_no' => 'required|max:50|unique:leases,lease_no,' . $lease->id,
            'resident_id' => 'required|exists:residents,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'area' => 'required|numeric|min:0',
            'lease_type' => 'required|in:1,2',
            'status' => 'nullable|in:1,2,3',
            'remark' => 'nullable|string',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $lease->update($validated);

        return response()->json($lease->load('resident'));
    }

    public function destroy(Lease $lease)
    {
        if ($lease->arrears()->unpaid()->exists()) {
            return response()->json(['message' => '该租约存在未缴费用，无法删除'], 400);
        }

        $lease->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function arrears(Lease $lease)
    {
        $arrears = $lease->arrears()->orderBy('bill_period', 'desc')->paginate(20);
        return response()->json($arrears);
    }

    public function generateBills(Request $request, Lease $lease)
    {
        $validated = $request->validate([
            'start_period' => 'required|regex:/^\d{4}-\d{2}$/',
            'end_period' => 'required|regex:/^\d{4}-\d{2}$/|after_or_equal:start_period',
            'property_fee' => 'nullable|numeric|min:0',
            'water_fee' => 'nullable|numeric|min:0',
            'electric_fee' => 'nullable|numeric|min:0',
            'gas_fee' => 'nullable|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
        ]);

        $start = $validated['start_period'];
        $end = $validated['end_period'];
        $bills = [];

        $startDate = new \DateTime($start . '-01');
        $endDate = new \DateTime($end . '-01');

        while ($startDate <= $endDate) {
            $period = $startDate->format('Y-m');

            $existing = Arrear::where('lease_id', $lease->id)
                ->where('bill_period', $period)
                ->first();

            if (!$existing) {
                $rentAmount = $lease->monthly_rent;
                $total = $rentAmount
                    + ($validated['property_fee'] ?? 0)
                    + ($validated['water_fee'] ?? 0)
                    + ($validated['electric_fee'] ?? 0)
                    + ($validated['gas_fee'] ?? 0)
                    + ($validated['other_fee'] ?? 0);

                $billDate = $startDate->format('Y-m-01');
                $dueDate = $startDate->format('Y-m-15');

                $bill = Arrear::create([
                    'bill_no' => Arrear::generateBillNo(),
                    'resident_id' => $lease->resident_id,
                    'lease_id' => $lease->id,
                    'bill_period' => $period,
                    'bill_date' => $billDate,
                    'due_date' => $dueDate,
                    'rent_amount' => $rentAmount,
                    'property_fee' => $validated['property_fee'] ?? 0,
                    'water_fee' => $validated['water_fee'] ?? 0,
                    'electric_fee' => $validated['electric_fee'] ?? 0,
                    'gas_fee' => $validated['gas_fee'] ?? 0,
                    'other_fee' => $validated['other_fee'] ?? 0,
                    'total_amount' => $total,
                    'paid_amount' => 0,
                    'unpaid_amount' => $total,
                    'late_fee' => 0,
                    'status' => 1,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);

                $bills[] = $bill;
            }

            $startDate->modify('+1 month');
        }

        return response()->json([
            'message' => '账单生成成功',
            'generated_count' => count($bills),
            'bills' => $bills,
        ]);
    }
}

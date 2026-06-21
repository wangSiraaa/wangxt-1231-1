<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\ResidentFamilyMember;
use Illuminate\Http\Request;

class ResidentController extends Controller
{
    public function index(Request $request)
    {
        $query = Resident::with(['activeLease', 'latestQualificationRecord']);

        if ($request->has('keyword') && $request->keyword) {
            $query->search($request->keyword);
        }

        if ($request->has('status') && $request->status) {
            $query->status($request->status);
        }

        if ($request->has('building') && $request->building) {
            $query->where('building', $request->building);
        }

        $residents = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        $residents->getCollection()->transform(function ($resident) {
            $resident->total_unpaid_amount = $resident->totalUnpaidAmount();
            $resident->can_register_emergency = $resident->canRegisterEmergencyMaintenance();
            $resident->can_renew = $resident->canRenewLease();
            $resident->full_address = $resident->full_address;
            return $resident;
        });

        return response()->json($residents);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_card' => 'required|unique:residents,id_card|size:18',
            'name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:1,2',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:200',
            'building' => 'nullable|string|max:20',
            'unit' => 'nullable|string|max:20',
            'room' => 'nullable|string|max:20',
            'house_area' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:1,2,3',
            'remark' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $resident = Resident::create($validated);

        return response()->json($resident->load(['activeLease', 'latestQualificationRecord']), 201);
    }

    public function show(Resident $resident)
    {
        $resident->load(['familyMembers', 'activeLease', 'latestQualificationRecord']);
        $resident->total_unpaid_amount = $resident->totalUnpaidAmount();
        $resident->can_register_emergency = $resident->canRegisterEmergencyMaintenance();
        $resident->can_renew = $resident->canRenewLease();
        $resident->full_address = $resident->full_address;

        return response()->json($resident);
    }

    public function update(Request $request, Resident $resident)
    {
        $validated = $request->validate([
            'id_card' => 'required|size:18|unique:residents,id_card,' . $resident->id,
            'name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:1,2',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:200',
            'building' => 'nullable|string|max:20',
            'unit' => 'nullable|string|max:20',
            'room' => 'nullable|string|max:20',
            'house_area' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:1,2,3',
            'remark' => 'nullable|string',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $resident->update($validated);

        return response()->json($resident->load(['activeLease', 'latestQualificationRecord']));
    }

    public function destroy(Resident $resident)
    {
        if ($resident->leases()->exists()) {
            return response()->json(['message' => '该住户存在租约，无法删除'], 400);
        }

        $resident->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function family(Resident $resident)
    {
        return response()->json($resident->familyMembers);
    }

    public function addFamilyMember(Request $request, Resident $resident)
    {
        $validated = $request->validate([
            'id_card' => 'required|size:18',
            'name' => 'required|string|max:50',
            'relation' => 'required|string|max:20',
            'gender' => 'required|in:1,2',
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
        ]);

        $member = $resident->familyMembers()->create($validated);

        return response()->json($member, 201);
    }

    public function deleteFamilyMember(ResidentFamilyMember $member)
    {
        $member->delete();
        return response()->json(['message' => '删除成功']);
    }

    public function arrearsSummary(Resident $resident)
    {
        $arrears = $resident->unpaidArrears()->with('lease')->get();
        $totalUnpaid = $arrears->sum('unpaid_amount');
        $threshold = config('system.arrears_threshold', 10000);

        return response()->json([
            'arrears' => $arrears,
            'total_unpaid' => $totalUnpaid,
            'threshold' => $threshold,
            'exceeds_threshold' => $totalUnpaid > $threshold,
        ]);
    }

    public function qualificationStatus(Resident $resident)
    {
        $latestRecord = $resident->latestQualificationRecord;
        $canRenew = $resident->canRenewLease();

        return response()->json([
            'latest_record' => $latestRecord,
            'can_renew' => $canRenew,
            'active_lease' => $resident->activeLease,
        ]);
    }

    public function recordPayment(Request $request, Resident $resident)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:1,2,3,4',
            'remark' => 'nullable|string',
        ]);

        $unpaidArrears = $resident->unpaidArrears()->orderBy('bill_period', 'asc')->get();

        if ($unpaidArrears->isEmpty()) {
            return response()->json(['message' => '该住户没有待缴账单'], 400);
        }

        $remainingAmount = $validated['amount'];
        $payments = [];
        $paymentMethodMap = [1 => '现金', 2 => '微信', 3 => '支付宝', 4 => '银行转账'];

        foreach ($unpaidArrears as $arrear) {
            if ($remainingAmount <= 0) {
                break;
            }

            $payAmount = min($remainingAmount, $arrear->unpaid_amount);

            $payment = $arrear->recordPayment(
                $payAmount,
                $paymentMethodMap[$validated['payment_method']] ?? '现金',
                null,
                $validated['remark'] ?? null,
                $request->user()->id
            );

            $payments[] = $payment;
            $remainingAmount -= $payAmount;
        }

        return response()->json([
            'message' => '缴费成功',
            'paid_amount' => $validated['amount'] - $remainingAmount,
            'remaining_amount' => $remainingAmount,
            'payments' => $payments,
            'total_unpaid' => $resident->totalUnpaidAmount(),
        ]);
    }

    public function leases(Resident $resident)
    {
        $leases = $resident->leases()->orderBy('created_at', 'desc')->get();

        $leases->transform(function ($lease) {
            $lease->total_arrears = $lease->calculateTotalArrears();
            $lease->is_expiring = $lease->isExpiring();
            return $lease;
        });

        return response()->json($leases);
    }

    public function maintenanceOrders(Resident $resident, Request $request)
    {
        $limit = $request->limit ?? 5;
        $orders = $resident->maintenanceOrders()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($orders);
    }

    public function qualificationRecords(Resident $resident)
    {
        $records = $resident->qualificationRecords()
            ->with('batch')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($records);
    }
}

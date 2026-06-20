<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arrear;
use Illuminate\Http\Request;

class ArrearController extends Controller
{
    public function index(Request $request)
    {
        $query = Arrear::with(['resident', 'lease']);

        if ($request->has('status') && $request->status) {
            if ($request->status === 'unpaid') {
                $query->unpaid();
            } elseif ($request->status === 'paid') {
                $query->paid();
            } elseif ($request->status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->has('resident_id') && $request->resident_id) {
            $query->where('resident_id', $request->resident_id);
        }

        if ($request->has('lease_id') && $request->lease_id) {
            $query->where('lease_id', $request->lease_id);
        }

        if ($request->has('bill_period') && $request->bill_period) {
            $query->byPeriod($request->bill_period);
        }

        if ($request->has('keyword') && $request->keyword) {
            $query->whereHas('resident', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('id_card', 'like', "%{$request->keyword}%");
            });
        }

        $arrears = $query->orderBy('bill_period', 'desc')->paginate($request->per_page ?? 20);

        $arrears->getCollection()->transform(function ($arrear) {
            $arrear->is_overdue = $arrear->isOverdue();
            $arrear->calculated_late_fee = $arrear->calculateLateFee();
            return $arrear;
        });

        return response()->json($arrears);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bill_no' => 'required|unique:arrears,bill_no|max:50',
            'resident_id' => 'required|exists:residents,id',
            'lease_id' => 'required|exists:leases,id',
            'bill_period' => 'required|regex:/^\d{4}-\d{2}$/',
            'bill_date' => 'required|date',
            'due_date' => 'required|date',
            'rent_amount' => 'nullable|numeric|min:0',
            'property_fee' => 'nullable|numeric|min:0',
            'water_fee' => 'nullable|numeric|min:0',
            'electric_fee' => 'nullable|numeric|min:0',
            'gas_fee' => 'nullable|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        $total = ($validated['rent_amount'] ?? 0)
            + ($validated['property_fee'] ?? 0)
            + ($validated['water_fee'] ?? 0)
            + ($validated['electric_fee'] ?? 0)
            + ($validated['gas_fee'] ?? 0)
            + ($validated['other_fee'] ?? 0)
            + ($validated['late_fee'] ?? 0);

        $validated['total_amount'] = $total;
        $validated['unpaid_amount'] = $total;
        $validated['paid_amount'] = 0;
        $validated['status'] = 1;
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $arrear = Arrear::create($validated);

        return response()->json($arrear->load(['resident', 'lease']), 201);
    }

    public function show(Arrear $arrear)
    {
        $arrear->load(['resident', 'lease', 'paymentRecords']);
        $arrear->is_overdue = $arrear->isOverdue();
        $arrear->calculated_late_fee = $arrear->calculateLateFee();

        return response()->json($arrear);
    }

    public function update(Request $request, Arrear $arrear)
    {
        if ($arrear->status === 3) {
            return response()->json(['message' => '已缴账单不能修改'], 400);
        }

        $validated = $request->validate([
            'bill_no' => 'required|max:50|unique:arrears,bill_no,' . $arrear->id,
            'bill_period' => 'required|regex:/^\d{4}-\d{2}$/',
            'bill_date' => 'required|date',
            'due_date' => 'required|date',
            'rent_amount' => 'nullable|numeric|min:0',
            'property_fee' => 'nullable|numeric|min:0',
            'water_fee' => 'nullable|numeric|min:0',
            'electric_fee' => 'nullable|numeric|min:0',
            'gas_fee' => 'nullable|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        $total = ($validated['rent_amount'] ?? 0)
            + ($validated['property_fee'] ?? 0)
            + ($validated['water_fee'] ?? 0)
            + ($validated['electric_fee'] ?? 0)
            + ($validated['gas_fee'] ?? 0)
            + ($validated['other_fee'] ?? 0)
            + ($validated['late_fee'] ?? 0);

        $validated['total_amount'] = $total;
        $validated['unpaid_amount'] = max(0, $total - $arrear->paid_amount);
        $validated['updated_by'] = $request->user()->id;

        if ($validated['unpaid_amount'] <= 0) {
            $validated['status'] = 3;
        } elseif ($arrear->paid_amount > 0) {
            $validated['status'] = 2;
        }

        $arrear->update($validated);

        return response()->json($arrear->load(['resident', 'lease']));
    }

    public function destroy(Arrear $arrear)
    {
        if ($arrear->paid_amount > 0) {
            return response()->json(['message' => '已有缴费记录的账单不能删除'], 400);
        }

        $arrear->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function pay(Request $request, Arrear $arrear)
    {
        if ($arrear->status === 3) {
            return response()->json(['message' => '该账单已缴清'], 400);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:现金,微信,支付宝,银行转账',
            'transaction_no' => 'nullable|string|max:100',
            'remark' => 'nullable|string',
        ]);

        if ($validated['amount'] > $arrear->unpaid_amount + $arrear->late_fee) {
            return response()->json(['message' => '缴费金额不能大于应缴金额'], 400);
        }

        $payment = $arrear->recordPayment(
            $validated['amount'],
            $validated['payment_method'],
            $validated['transaction_no'] ?? null,
            $validated['remark'] ?? null,
            $request->user()->id
        );

        return response()->json([
            'message' => '缴费成功',
            'payment' => $payment,
            'arrear' => $arrear->fresh(['resident', 'lease', 'paymentRecords']),
        ]);
    }

    public function paymentRecords(Arrear $arrear)
    {
        return response()->json($arrear->paymentRecords()->orderBy('created_at', 'desc')->get());
    }

    public function summary()
    {
        $totalUnpaid = Arrear::unpaid()->sum('unpaid_amount');
        $totalOverdue = Arrear::overdue()->count();
        $totalPaidThisMonth = Arrear::where('status', 3)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('paid_amount');
        $totalBillsThisMonth = Arrear::whereMonth('bill_date', now()->month)
            ->whereYear('bill_date', now()->year)
            ->count();

        return response()->json([
            'total_unpaid' => $totalUnpaid,
            'total_overdue_count' => $totalOverdue,
            'total_paid_this_month' => $totalPaidThisMonth,
            'total_bills_this_month' => $totalBillsThisMonth,
        ]);
    }
}

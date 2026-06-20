<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Arrear;
use App\Models\MaintenanceOrder;
use App\Models\QualificationBatch;
use App\Models\Resident;
use App\Models\Lease;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $residentCount = Resident::count();
        $activeLeaseCount = Lease::active()->count();
        $totalArrearsAmount = Arrear::unpaid()->sum('unpaid_amount');
        $overdueArrearsCount = Arrear::overdue()->count();
        $pendingMaintenanceCount = MaintenanceOrder::whereIn('status', [1, 2, 3])->count();
        $urgentMaintenanceCount = MaintenanceOrder::whereIn('status', [1, 2, 3])->urgent()->count();
        $pendingQualificationCount = QualificationBatch::draft()->count();
        $expiringLeaseCount = Lease::expiring(90)->count();
        $failQualificationCount = 0;
        $latestBatch = QualificationBatch::latest()->first();
        if ($latestBatch) {
            $failQualificationCount = $latestBatch->fail_count;
        }

        return response()->json([
            'resident_count' => $residentCount,
            'active_lease_count' => $activeLeaseCount,
            'total_arrears_amount' => $totalArrearsAmount,
            'overdue_arrears_count' => $overdueArrearsCount,
            'pending_maintenance_count' => $pendingMaintenanceCount,
            'urgent_maintenance_count' => $urgentMaintenanceCount,
            'pending_qualification_count' => $pendingQualificationCount,
            'expiring_lease_count' => $expiringLeaseCount,
            'fail_qualification_count' => $failQualificationCount,
        ]);
    }
}

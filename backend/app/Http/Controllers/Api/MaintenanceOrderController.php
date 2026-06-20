<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePhoto;
use App\Models\MaintenanceMaterial;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaintenanceOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceOrder::with(['resident', 'assignee']);

        if ($request->has('status') && $request->status) {
            $query->status($request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->type($request->type);
        }

        if ($request->has('resident_id') && $request->resident_id) {
            $query->where('resident_id', $request->resident_id);
        }

        if ($request->has('assigned_to') && $request->assigned_to) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->has('urgent') && $request->urgent) {
            $query->urgent();
        }

        if ($request->has('keyword') && $request->keyword) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->keyword}%")
                    ->orWhereHas('resident', function ($subQ) use ($request) {
                        $subQ->where('name', 'like', "%{$request->keyword}%")
                            ->orWhere('id_card', 'like', "%{$request->keyword}%");
                    });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        $orders->getCollection()->transform(function ($order) {
            $order->can_accept = $order->canBeAccepted();
            $order->can_start = $order->canBeStarted();
            $order->can_complete = $order->canBeCompleted();
            $order->can_close = $order->canClose();
            $order->can_cancel = $order->canBeCancelled();
            $order->can_add_photos = $order->canAddPhotos();
            return $order;
        });

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'type' => 'required|in:1,2',
            'category' => 'required|in:1,2,3,4,5',
            'title' => 'required|string|max:200',
            'description' => 'required|string',
            'contact_person' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:20',
            'appointment_time' => 'nullable|date',
            'urgency_level' => 'nullable|in:1,2,3',
        ]);

        $resident = Resident::findOrFail($validated['resident_id']);

        if ($resident->canRegisterEmergencyMaintenance() && $validated['type'] !== 2) {
            return response()->json([
                'message' => '该住户欠费超过阈值，仅允许登记紧急维修事项',
                'emergency_only' => true,
            ], 400);
        }

        $validated['order_no'] = MaintenanceOrder::generateOrderNo();
        $validated['lease_id'] = $resident->activeLease?->id;
        $validated['status'] = 1;
        $validated['urgency_level'] = $validated['urgency_level'] ?? ($validated['type'] === 2 ? 2 : 1);
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $order = MaintenanceOrder::create($validated);

        return response()->json($order->load(['resident', 'assignee']), 201);
    }

    public function show(MaintenanceOrder $order)
    {
        $order->load(['resident', 'assignee', 'photos', 'materials', 'timelines']);
        $order->can_accept = $order->canBeAccepted();
        $order->can_start = $order->canBeStarted();
        $order->can_complete = $order->canBeCompleted();
        $order->can_close = $order->canClose();
        $order->can_cancel = $order->canBeCancelled();
        $order->can_add_photos = $order->canAddPhotos();

        return response()->json($order);
    }

    public function update(Request $request, MaintenanceOrder $order)
    {
        if (!in_array($order->status, [1, 2])) {
            return response()->json(['message' => '只有待接单或已接单状态的工单可以修改'], 400);
        }

        $validated = $request->validate([
            'type' => 'required|in:1,2',
            'category' => 'required|in:1,2,3,4,5',
            'title' => 'required|string|max:200',
            'description' => 'required|string',
            'contact_person' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:20',
            'appointment_time' => 'nullable|date',
            'urgency_level' => 'nullable|in:1,2,3',
        ]);

        $validated['updated_by'] = $request->user()->id;

        $order->update($validated);

        return response()->json($order->load(['resident', 'assignee']));
    }

    public function destroy(MaintenanceOrder $order)
    {
        if (!in_array($order->status, [1, 6])) {
            return response()->json(['message' => '只有待接单或已取消状态的工单可以删除'], 400);
        }

        $order->delete();

        return response()->json(['message' => '删除成功']);
    }

    public function accept(Request $request, MaintenanceOrder $order)
    {
        try {
            $order->accept($request->user()->id, $request->user()->name);
            return response()->json([
                'message' => '接单成功',
                'order' => $order->fresh(['resident', 'assignee', 'timelines']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function start(Request $request, MaintenanceOrder $order)
    {
        try {
            $order->start($request->user()->id, $request->user()->name);
            return response()->json([
                'message' => '开始维修',
                'order' => $order->fresh(['resident', 'assignee', 'timelines']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function complete(Request $request, MaintenanceOrder $order)
    {
        $validated = $request->validate([
            'repair_result' => 'required|string',
            'material_cost' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $order->complete(
                $request->user()->id,
                $request->user()->name,
                $validated['repair_result'],
                $validated['material_cost'] ?? 0,
                $validated['labor_cost'] ?? 0
            );
            return response()->json([
                'message' => '维修完工',
                'order' => $order->fresh(['resident', 'assignee', 'timelines']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function close(Request $request, MaintenanceOrder $order)
    {
        try {
            $order->close($request->user()->id, $request->user()->name);
            return response()->json([
                'message' => '结案成功',
                'order' => $order->fresh(['resident', 'assignee', 'timelines']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancel(Request $request, MaintenanceOrder $order)
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|string',
        ]);

        try {
            $order->cancel($request->user()->id, $request->user()->name, $validated['cancel_reason']);
            return response()->json([
                'message' => '工单已取消',
                'order' => $order->fresh(['resident', 'assignee', 'timelines']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function assign(Request $request, MaintenanceOrder $order)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        try {
            $order->assign($request->user()->id, $request->user()->name, $validated['assigned_to']);
            return response()->json([
                'message' => '派单成功',
                'order' => $order->fresh(['resident', 'assignee', 'timelines']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function uploadPhoto(Request $request, MaintenanceOrder $order)
    {
        if (!$order->canAddPhotos()) {
            return response()->json(['message' => '当前状态不允许上传照片'], 400);
        }

        $validated = $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            'photo_type' => 'nullable|in:before,repair,after,completion',
            'description' => 'nullable|string',
        ]);

        $path = $request->file('photo')->store('maintenance/' . $order->id, 'public');

        $photo = MaintenancePhoto::create([
            'maintenance_order_id' => $order->id,
            'photo_type' => $validated['photo_type'] ?? 'completion',
            'file_path' => $path,
            'file_name' => $request->file('photo')->getClientOriginalName(),
            'file_size' => $request->file('photo')->getSize(),
            'description' => $validated['description'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        if ($photo->photo_type === 'completion') {
            $order->has_photos = true;
            $order->save();
        }

        return response()->json($photo, 201);
    }

    public function photos(MaintenanceOrder $order)
    {
        return response()->json($order->photos()->with('uploader')->get());
    }

    public function timeline(MaintenanceOrder $order)
    {
        return response()->json($order->timelines()->with('operator')->orderBy('created_at', 'desc')->get());
    }

    public function addMaterial(Request $request, MaintenanceOrder $order)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:100',
            'specification' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:10',
            'unit_price' => 'required|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        $material = $order->materials()->create($validated);

        $totalMaterialCost = $order->materials()->sum('amount');
        $order->material_cost = $totalMaterialCost;
        $order->total_cost = $totalMaterialCost + $order->labor_cost;
        $order->save();

        return response()->json($material, 201);
    }

    public function deleteMaterial(MaintenanceMaterial $material)
    {
        $order = $material->maintenanceOrder;
        $material->delete();

        $totalMaterialCost = $order->materials()->sum('amount');
        $order->material_cost = $totalMaterialCost;
        $order->total_cost = $totalMaterialCost + $order->labor_cost;
        $order->save();

        return response()->json(['message' => '删除成功']);
    }
}

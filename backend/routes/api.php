<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user()->load('roles');
});

Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats']);

    Route::apiResource('residents', App\Http\Controllers\Api\ResidentController::class);
    Route::get('/residents/{resident}/family', [App\Http\Controllers\Api\ResidentController::class, 'family']);
    Route::post('/residents/{resident}/family', [App\Http\Controllers\Api\ResidentController::class, 'addFamilyMember']);
    Route::delete('/resident-family/{member}', [App\Http\Controllers\Api\ResidentController::class, 'deleteFamilyMember']);
    Route::get('/residents/{resident}/arrears-summary', [App\Http\Controllers\Api\ResidentController::class, 'arrearsSummary']);
    Route::get('/residents/{resident}/qualification-status', [App\Http\Controllers\Api\ResidentController::class, 'qualificationStatus']);

    Route::apiResource('leases', App\Http\Controllers\Api\LeaseController::class);
    Route::get('/leases/{lease}/arrears', [App\Http\Controllers\Api\LeaseController::class, 'arrears']);
    Route::post('/leases/{lease}/generate-bills', [App\Http\Controllers\Api\LeaseController::class, 'generateBills']);
    Route::post('/lease-renewals', [App\Http\Controllers\Api\LeaseRenewalController::class, 'store']);
    Route::get('/lease-renewals/pending', [App\Http\Controllers\Api\LeaseRenewalController::class, 'pending']);
    Route::post('/lease-renewals/{application}/approve', [App\Http\Controllers\Api\LeaseRenewalController::class, 'approve']);
    Route::post('/lease-renewals/{application}/reject', [App\Http\Controllers\Api\LeaseRenewalController::class, 'reject']);

    Route::apiResource('arrears', App\Http\Controllers\Api\ArrearController::class);
    Route::post('/arrears/{arrear}/pay', [App\Http\Controllers\Api\ArrearController::class, 'pay']);
    Route::get('/arrears/{arrear}/payment-records', [App\Http\Controllers\Api\ArrearController::class, 'paymentRecords']);
    Route::get('/arrears-summary', [App\Http\Controllers\Api\ArrearController::class, 'summary']);

    Route::apiResource('maintenance-orders', App\Http\Controllers\Api\MaintenanceOrderController::class);
    Route::post('/maintenance-orders/{order}/accept', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'accept']);
    Route::post('/maintenance-orders/{order}/start', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'start']);
    Route::post('/maintenance-orders/{order}/complete', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'complete']);
    Route::post('/maintenance-orders/{order}/close', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'close']);
    Route::post('/maintenance-orders/{order}/cancel', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'cancel']);
    Route::post('/maintenance-orders/{order}/assign', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'assign']);
    Route::post('/maintenance-orders/{order}/photos', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'uploadPhoto']);
    Route::get('/maintenance-orders/{order}/photos', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'photos']);
    Route::get('/maintenance-orders/{order}/timeline', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'timeline']);
    Route::post('/maintenance-orders/{order}/materials', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'addMaterial']);
    Route::delete('/maintenance-materials/{material}', [App\Http\Controllers\Api\MaintenanceOrderController::class, 'deleteMaterial']);

    Route::apiResource('qualification-batches', App\Http\Controllers\Api\QualificationBatchController::class);
    Route::post('/qualification-batches/{batch}/publish', [App\Http\Controllers\Api\QualificationBatchController::class, 'publish']);
    Route::post('/qualification-batches/{batch}/complete', [App\Http\Controllers\Api\QualificationBatchController::class, 'complete']);
    Route::post('/qualification-batches/import', [App\Http\Controllers\Api\QualificationBatchController::class, 'import']);
    Route::get('/qualification-batches/{batch}/records', [App\Http\Controllers\Api\QualificationBatchController::class, 'records']);
    Route::post('/qualification-records/{record}/pass', [App\Http\Controllers\Api\QualificationRecordController::class, 'pass']);
    Route::post('/qualification-records/{record}/fail', [App\Http\Controllers\Api\QualificationRecordController::class, 'fail']);
    Route::apiResource('qualification-records', App\Http\Controllers\Api\QualificationRecordController::class);

    Route::get('/system-configs', [App\Http\Controllers\Api\SystemConfigController::class, 'index']);
    Route::post('/system-configs', [App\Http\Controllers\Api\SystemConfigController::class, 'store']);
    Route::get('/system-configs/public', [App\Http\Controllers\Api\SystemConfigController::class, 'public']);
});

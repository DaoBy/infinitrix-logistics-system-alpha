<?php
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\StickerController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\UtilitiesController;
use App\Http\Controllers\TruckMaintenanceController;
use App\Http\Controllers\TruckComponentController;
use App\Http\Controllers\CargoAssignmentController;
use App\Http\Controllers\RequestDeliveryController;
use App\Http\Controllers\DriverTruckAssignmentController;
use App\Http\Controllers\RegionTravelDurationController;
use App\Http\Controllers\DeliveryCompletionController;
use App\Http\Controllers\PackageVerificationController;
use App\Http\Controllers\Customer\TransactionController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\DeliveryRequestController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\StaffDashboardController;
use App\Http\Controllers\CollectorPaymentController;
use App\Http\Controllers\PriceMatrixController;
use App\Http\Controllers\RequestApprovalController;
use App\Http\Controllers\AddressBookController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\WaybillController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PackageTransferController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\CustomerUpdateRequestController;
use App\Http\Controllers\PackageTrackingController;
use App\Http\Controllers\CustomerPaymentController;
use App\Models\ReportsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Truck;
use App\Http\Controllers\PaymentController;

// =============================================================================
// PUBLIC ROUTES
// =============================================================================
// Add to routes/web.php
Route::get('/debug-new-assignment', function() {
    // Get the latest assignment
    $assignment = \App\Models\DriverTruckAssignment::with(['driver', 'truck'])
        ->latest()
        ->first();
    
    if (!$assignment) {
        return response()->json(['error' => 'No assignments found']);
    }
    
    // Check manifests by assignment ID
    $manifestsByAssignment = \App\Models\Manifest::where('driver_truck_assignment_id', $assignment->id)->get();
    
    // Check manifests by driver/truck combo
    $manifestsByDriverTruck = \App\Models\Manifest::where('driver_id', $assignment->driver_id)
        ->where('truck_id', $assignment->truck_id)
        ->get();
    
    // Check finalized manifests
    $finalizedByAssignment = \App\Models\Manifest::where('driver_truck_assignment_id', $assignment->id)
        ->where('status', 'finalized')
        ->exists();
        
    $finalizedByDriverTruck = \App\Models\Manifest::where('driver_id', $assignment->driver_id)
        ->where('truck_id', $assignment->truck_id)
        ->where('status', 'finalized')
        ->exists();
    
    return response()->json([
        'assignment' => [
            'id' => $assignment->id,
            'driver_name' => $assignment->driver->name ?? 'N/A',
            'truck_plate' => $assignment->truck->license_plate ?? 'N/A',
            'current_status' => $assignment->current_status,
            'is_active' => $assignment->is_active,
            'created_at' => $assignment->created_at,
        ],
        'manifests_found' => [
            'by_assignment_id' => $manifestsByAssignment->count(),
            'by_driver_truck' => $manifestsByDriverTruck->count(),
            'finalized_by_assignment' => $finalizedByAssignment,
            'finalized_by_driver_truck' => $finalizedByDriverTruck,
        ],
        'manifests_details' => [
            'by_assignment' => $manifestsByAssignment,
            'by_driver_truck' => $manifestsByDriverTruck,
        ]
    ]);
});
// Add to web.php
Route::get('/debug-all-assignments', function() {
    $assignments = \App\Models\DriverTruckAssignment::with(['driver', 'truck'])
        ->latest()
        ->get()
        ->map(function($assignment) {
            return [
                'id' => $assignment->id,
                'driver_name' => $assignment->driver->name ?? 'N/A',
                'truck_plate' => $assignment->truck->license_plate ?? 'N/A',
                'current_status' => $assignment->current_status,
                'is_active' => $assignment->is_active,
                'created_at' => $assignment->created_at,
                'has_finalized_manifest' => app(\App\Http\Controllers\DriverTruckAssignmentController::class)->hasFinalizedManifest($assignment)
            ];
        });
    
    return response()->json($assignments);
});

Route::get('/debug-manifests/{assignmentId}', function($assignmentId) {
    $assignment = \App\Models\DriverTruckAssignment::find($assignmentId);
    
    if (!$assignment) {
        return response()->json(['error' => 'Assignment not found']);
    }

    $manifestsByAssignment = \App\Models\Manifest::where('driver_truck_assignment_id', $assignment->id)
        ->get();

    $manifestsByDriverTruck = \App\Models\Manifest::where('driver_id', $assignment->driver_id)
        ->where('truck_id', $assignment->truck_id)
        ->get();

    return response()->json([
        'assignment' => [
            'id' => $assignment->id,
            'driver_id' => $assignment->driver_id,
            'truck_id' => $assignment->truck_id,
            'created_at' => $assignment->created_at,
        ],
        'manifests_by_assignment_id' => $manifestsByAssignment,
        'manifests_by_driver_truck' => $manifestsByDriverTruck,
        'has_any_manifests' => $manifestsByAssignment->count() > 0 || $manifestsByDriverTruck->count() > 0
    ]);
});


Route::get('/debug/skip-cooldown-direct', function() {
    $driver = auth()->user();
    $assignment = $driver->currentTruckAssignment;
    
    if (!$assignment) {
        return response()->json(['error' => 'No assignment found']);
    }
    
    \Log::info("🧪 DIRECT TEST: Skip cooldown", [
        'assignment_id' => $assignment->id,
        'current_status' => $assignment->current_status
    ]);
    
    try {
        $result = $assignment->skipCooldown();
        
        return response()->json([
            'success' => $result,
            'assignment' => [
                'id' => $assignment->id,
                'status' => $assignment->current_status,
                'available_for_backhaul' => $assignment->available_for_backhaul
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

Route::get('/debug/assignment-state', function() {
    $driver = auth()->user();
    $assignment = $driver->currentTruckAssignment;

    if (!$assignment) {
        return response()->json(['error' => 'No active assignment']);
    }

    return response()->json([
        'assignment' => [
            'id' => $assignment->id,
            'current_status' => $assignment->current_status,
            'is_final_cooldown' => $assignment->is_final_cooldown,
            'cooldown_ends_at' => $assignment->cooldown_ends_at,
            'available_for_backhaul' => $assignment->available_for_backhaul,
            'all_packages_delivered' => $assignment->allPackagesDelivered(),
            'driver_region_id' => $driver->current_region_id,
            'home_region_id' => $assignment->region_id,
        ],
        'can_skip_cooldown' => (
            $assignment->current_status === \App\Models\DriverTruckAssignment::STATUS_COOLDOWN &&
            !$assignment->is_final_cooldown
        )
    ]);
});
// In routes/web.php
Route::get('/debug/package-relationships', function() {
    $driver = auth()->user();
    
    $packages = \App\Models\Package::with([
        'deliveryRequest.deliveryOrder',
        'deliveryRequest.dropOffRegion',
        'currentRegion'
    ])
    ->whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
        $query->where('driver_id', $driver->id);
    })
    ->where('status', 'in_transit')
    ->get();
    
    return response()->json([
        'packages_count' => $packages->count(),
        'packages' => $packages->map(function($pkg) {
            return [
                'id' => $pkg->id,
                'has_delivery_request' => !is_null($pkg->deliveryRequest),
                'has_delivery_order' => $pkg->deliveryRequest && !is_null($pkg->deliveryRequest->deliveryOrder),
                'current_region_id' => $pkg->current_region_id,
                'driver_region_id' => auth()->user()->current_region_id
            ];
        })
    ]);
});


// Home & Static Pages
Route::get('/', fn() => Inertia::render('Customer/Home'))->name('customer.home');
Route::get('/about-us', fn() => Inertia::render('Customer/AboutUs'))->name('about.us');
Route::get('/services', fn() => Inertia::render('Customer/Services'))->name('services');
Route::get('/employee', fn() => Inertia::render('Auth/EmployeeLanding'))->name('employee.landing');
Route::get('/sample', fn() => Inertia::render('ComponentsExample'))->name('sample');
Route::get('/tablesample', fn() => Inertia::render('TableSample'))->name('tablesample');

// Contact Routes
Route::get('/contact-us', [ContactController::class, 'index'])->name('contact.us');
Route::post('/contact-us', [ContactController::class, 'submit'])->name('contact.submit');

// Package Tracking (Public)
Route::get('/tracking', [PackageTrackingController::class, 'showTrackingLanding'])->name('tracking.landing');
Route::get('/tracking/{itemCode}', [PackageTrackingController::class, 'publicTrackPackage'])->name('tracking.public');

// =============================================================================
// API ROUTES
// =============================================================================

Route::prefix('api')->group(function () {
    // Delivery regions API
    Route::get('/delivery/regions', [RegionController::class, 'getActiveRegions']);
    
    // Price matrix API
    Route::get('/price-matrix', [PriceMatrixController::class, 'index']);
    
    // Debug endpoint
    Route::get('/debug-manifest/{assignmentId}', [CargoAssignmentController::class, 'debugManifest']);
    
    Route::fallback(function () {
        return response()->json([
            'message' => 'Endpoint not found'
        ], 404);
    });
});
// =============================================================================
// AUTHENTICATED ROUTES (PROFILE)
// =============================================================================

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/update-with-verification', [ProfileController::class, 'updateWithVerification'])->name('profile.update.with-verification');
    Route::post('/profile/verify-email-change', [ProfileController::class, 'verifyEmailChange'])->name('profile.verify.email.change');
    
    // Staff/Driver Package Tracking
    Route::get('/packages/{package}/track', [PackageTrackingController::class, 'trackPackage'])->name('packages.track');
});

// =============================================================================
// CUSTOMER ROUTES
// =============================================================================

Route::middleware(['auth', 'role:customer', 'verified', \App\Http\Middleware\EnsureProfileComplete::class])->group(function () {
   
    // Customer Dashboard & Deliveries
    Route::get('/my-deliveries', [DeliveryRequestController::class, 'index'])->name('customer.deliveries.index');

    // Customer Payments
    Route::get('/my-deliveries/{delivery}/pay', [CustomerPaymentController::class, 'create'])->name('customer.payments.create');
    Route::post('/my-deliveries/{delivery}/pay', [CustomerPaymentController::class, 'store'])->name('customer.payments.store');
    Route::get('/my-payments/{payment}', [CustomerPaymentController::class, 'show'])->name('customer.payments.show');
    Route::get('/deliveries/{delivery}/payments/resubmit/{payment}', [CustomerPaymentController::class, 'resubmit'])->name('customer.payments.resubmit');
    Route::post('/deliveries/{delivery}/payments/{payment}', [CustomerPaymentController::class, 'update'])->name('customer.payments.update');

    // Delivery Requests
    Route::prefix('delivery-requests')->group(function () {
        Route::get('/', [DeliveryRequestController::class, 'index'])->name('customer.delivery-requests.index');
        Route::get('/create', [RequestDeliveryController::class, 'create'])->name('customer.delivery-requests.create');
        Route::post('/', [RequestDeliveryController::class, 'store'])->name('customer.delivery-requests.store');
        Route::post('/calculate-price', [RequestDeliveryController::class, 'calculatePrice'])->name('customer.delivery-requests.calculate-price');
        Route::get('/{deliveryRequest}', [DeliveryRequestController::class, 'show'])->name('customer.delivery-requests.show');
        Route::get('/{deliveryRequest}/edit', [DeliveryRequestController::class, 'edit'])->name('customer.delivery-requests.edit');
        Route::put('/{deliveryRequest}', [DeliveryRequestController::class, 'update'])->name('customer.delivery-requests.update');
        Route::delete('/{deliveryRequest}', [DeliveryRequestController::class, 'destroy'])->name('customer.delivery-requests.destroy');
        Route::post('/upload-photos', [RequestDeliveryController::class, 'uploadPhotos'])->name('customer.delivery-requests.upload-photos');
    });

    // Profile Update Requests
Route::prefix('profile')->group(function () {
    // Single profile management route - handles both direct editing and update requests
    Route::get('/update', [CustomerProfileController::class, 'create'])->name('customer.profile.update');
    Route::post('/update', [CustomerProfileController::class, 'store'])->name('customer.profile.update.store');
});


    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    });

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('customer.transactions.index');
        Route::get('/{deliveryOrder}', [TransactionController::class, 'show'])->name('customer.transactions.show');
    });
});

// =============================================================================
// ADMIN & STAFF ROUTES
// =============================================================================

Route::middleware(['auth', 'role:admin,staff'])->group(function () {
    
    // Reports
    Route::get('/admin/reports', [App\Http\Controllers\ReportsController::class, 'dashboard'])->name('reports.dashboard');
    Route::get('/admin/reports/documents', [App\Http\Controllers\ReportsController::class, 'documents'])->name('reports.documents');
    Route::get('/admin/reports/export', [App\Http\Controllers\ReportsController::class, 'exportPdf'])->name('reports.export');

    // Refunds
    Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::get('/refunds/create', [RefundController::class, 'create'])->name('refunds.create');
    Route::post('/refunds', [RefundController::class, 'store'])->name('refunds.store');
    Route::get('/refunds/{refund}', [RefundController::class, 'show'])->name('refunds.show');
    Route::get('/refunds/{refund}/edit', [RefundController::class, 'edit'])->name('refunds.edit');
    Route::put('/refunds/{refund}', [RefundController::class, 'update'])->name('refunds.update');
    Route::post('/refunds/{refund}/process', [RefundController::class, 'process'])->name('refunds.process');
    Route::get('/refunds/search/delivery-requests', [RefundController::class, 'searchDeliveryRequests'])->name('refunds.search-delivery-requests');
    Route::get('/refunds/calculate/max-refund', [RefundController::class, 'calculateMaxRefund'])->name('refunds.calculate-max-refund');

    // Deliveries Management
    Route::prefix('deliveries')->group(function () {
        Route::get('/', [RequestApprovalController::class, 'index'])->name('deliveries.index');
        Route::get('/pending', [RequestApprovalController::class, 'pending'])->name('deliveries.pending');
        Route::get('/rejected', [RequestApprovalController::class, 'rejected'])->name('deliveries.rejected');
        Route::get('/{delivery}', [RequestApprovalController::class, 'show'])->name('deliveries.show');
        Route::get('/{delivery}/edit', [RequestApprovalController::class, 'edit'])->name('deliveries.edit');
        Route::put('/{delivery}', [RequestApprovalController::class, 'update'])->name('deliveries.update');
        Route::post('/{delivery}/approve', [RequestApprovalController::class, 'approve'])->name('deliveries.approve');
        Route::post('/{delivery}/reject', [RequestApprovalController::class, 'reject'])->name('deliveries.reject');
        Route::post('/bulk-approve', [RequestApprovalController::class, 'bulkApprove'])->name('deliveries.bulk-approve');
        Route::post('/bulk-reject', [RequestApprovalController::class, 'bulkReject'])->name('deliveries.bulk-reject');
    });

   // Driver-Truck Assignments
Route::prefix('driver-truck-assignments')->name('driver-truck-assignments.')->group(function () {
    Route::get('/', [DriverTruckAssignmentController::class, 'index'])->name('index');
    Route::post('/store', [DriverTruckAssignmentController::class, 'store'])->name('store');
    Route::get('/available-resources', [DriverTruckAssignmentController::class, 'getAvailableResources'])->name('available-resources');
    Route::get('/all-resources', [DriverTruckAssignmentController::class, 'getAllResourcesForRegion'])->name('all-resources');
    Route::get('/debug/database-state', [DriverTruckAssignmentController::class, 'checkDatabaseState'])->name('debug.database-state');

    // ADD THIS ROUTE FOR MANIFEST STATUS CHECK
    Route::get('/{assignment}/check-manifest-status', [DriverTruckAssignmentController::class, 'checkManifestStatus'])->name('check-manifest-status');

    Route::prefix('{assignment}')->group(function () {
        Route::post('/cancel', [DriverTruckAssignmentController::class, 'cancel'])->name('cancel');
        Route::put('/update-status', [DriverTruckAssignmentController::class, 'updateDriverStatus'])->name('update-status');
        Route::get('/status-timeline', [DriverTruckAssignmentController::class, 'getDriverStatusTimeline'])->name('status-timeline');
        Route::get('/status-timeline/show', [DriverTruckAssignmentController::class, 'showStatusTimeline'])->name('status-timeline.show');
        Route::post('/complete-cooldown', [DriverTruckAssignmentController::class, 'completeCooldown'])->name('complete-cooldown');
        Route::post('/skip-cooldown', [DriverTruckAssignmentController::class, 'skipCooldown'])->name('skip-cooldown');
        Route::post('/enable-backhaul', [DriverTruckAssignmentController::class, 'enableBackhaul'])->name('enable-backhaul');
        Route::post('/disable-backhaul', [DriverTruckAssignmentController::class, 'disableBackhaul'])->name('disable-backhaul');
        Route::post('/check-backhaul-eligibility', [DriverTruckAssignmentController::class, 'checkBackhaulEligibility'])->name('check-backhaul-eligibility');
        Route::post('/verify-return', [DriverTruckAssignmentController::class, 'verifyDriverReturn'])->name('verify-return');
        Route::post('/force-return', [DriverTruckAssignmentController::class, 'forceReturnToBase'])->name('force-return');
        Route::post('/confirm-return', [DriverTruckAssignmentController::class, 'confirmReturnToBase'])->name('confirm-return');
        Route::post('/force-complete', [DriverTruckAssignmentController::class, 'forceCompleteAssignment'])->name('force-complete');
    });


        
    });

// Cargo Assignments
Route::prefix('cargo-assignments')->name('cargo-assignments.')->group(function () {
    Route::get('/', [CargoAssignmentController::class, 'index'])->name('index');
    Route::get('/{deliveryOrder}', [CargoAssignmentController::class, 'show'])->name('show');

    Route::prefix('assign')->name('assign.')->group(function () {
        // Route::get('/suggestions', [CargoAssignmentController::class, 'getSuggestedAssignments'])->name('suggestions'); // REMOVED
        // Route::post('/validate', [CargoAssignmentController::class, 'validateAssignment'])->name('validate'); // REMOVED
        Route::post('/batch', [CargoAssignmentController::class, 'batchAssign'])->name('batch');
        Route::post('/{deliveryRequest}', [CargoAssignmentController::class, 'assign'])->name('single');
    });


    Route::get('/delivery-orders/{deliveryOrder}/manifest-status', [CargoAssignmentController::class, 'checkManifestStatus']);
    // Route::prefix('backhaul')->name('backhaul.')->group(function () { // REMOVED ENTIRE GROUP
        // Route::post('/{assignment}/enable', [CargoAssignmentController::class, 'enableBackhaul'])->name('enable'); // REMOVED
        // Route::post('/{assignment}/quick-assign', [CargoAssignmentController::class, 'quickBackhaulAssign'])->name('quick-assign'); // REMOVED
    // });

    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::post('/{deliveryOrder}/cancel', [CargoAssignmentController::class, 'cancelDeliveryOrderAssignment'])->name('cancel');
    });

    Route::prefix('dispatch')->name('dispatch.')->group(function () {
        Route::post('/{assignment}', [CargoAssignmentController::class, 'dispatch'])->name('driver-truck-set');
        Route::get('/{assignment}/validate', [CargoAssignmentController::class, 'validateDispatch'])->name('validate');
    });

    // Route::get('/backhaul/metrics', [CargoAssignmentController::class, 'getBackhaulMetrics'])->name('backhaul.metrics'); // REMOVED
    // Route::get('/debug/assignments', [CargoAssignmentController::class, 'debugAssignments'])->name('debug.assignments'); // REMOVED
});

    // Delivery Completion
    Route::prefix('cargo-assignments/delivery-completion')->name('delivery-completion.')->group(function() {
        Route::get('/ready-for-completion', [DeliveryCompletionController::class, 'readyForCompletion'])->name('ready-for-completion');
        Route::get('/{order}/show-form', [DeliveryCompletionController::class, 'showCompletionForm'])->name('show-form');
        Route::post('/{order}/process', [DeliveryCompletionController::class, 'processCompletion'])->name('process');
    });

    // Region Travel Durations
    Route::prefix('region-durations')->name('region-durations.')->group(function () {
        Route::get('/', [RegionTravelDurationController::class, 'index'])->name('index');
        Route::post('/', [RegionTravelDurationController::class, 'store'])->name('store');
        Route::put('/{region_duration}', [RegionTravelDurationController::class, 'update'])->name('update');
        Route::delete('/{region_duration}', [RegionTravelDurationController::class, 'destroy'])->name('destroy');
    });

    // Waybills
    Route::prefix('waybills')->name('waybills.')->group(function () {
        Route::get('/', [WaybillController::class, 'index'])->name('index');
        Route::get('/{waybill}', [WaybillController::class, 'show'])->name('show');
        Route::post('/generate/{deliveryRequest}', [WaybillController::class, 'generate'])->name('generate');
        Route::get('/billing/{deliveryRequest}', [WaybillController::class, 'billing'])->name('billing');
        Route::post('/generate-from-manifest/{truck}', [WaybillController::class, 'generateFromManifest'])->name('generateFromManifest');
        Route::get('/download/{waybill}', [WaybillController::class, 'download'])->name('download');
        Route::get('/waybills/{waybill}/preview', [WaybillController::class, 'preview'])->name('preview');
        Route::get('/download-manifest/{manifest}', [WaybillController::class, 'downloadManifest'])->name('downloadManifest');
    });

    // Manifests
    Route::prefix('admin')->group(function () {
Route::get('/manifests', [ManifestController::class, 'index'])->name('manifests.index');
        Route::get('/manifests/create/{truck}', [ManifestController::class, 'create'])->name('manifests.create');
        Route::post('/manifests/{truck}', [ManifestController::class, 'store'])->name('manifests.store');
        Route::get('/manifests/{manifest}', [ManifestController::class, 'show'])->name('manifests.show');
        Route::post('/manifests/{manifest}/finalize', [ManifestController::class, 'finalize'])->name('manifests.finalize');
        Route::get('/manifests/{manifest}/print', [ManifestController::class, 'print'])->name('manifests.print');
        Route::delete('/manifests/{manifest}', [ManifestController::class, 'destroy'])->name('manifests.destroy');
    });

    // Payment Management
    Route::prefix('staff/payments')->name('staff.payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'dashboard'])->name('dashboard');
        Route::get('verify/{payment}', [PaymentController::class, 'verifyView'])->name('verify');
        Route::post('verify/{payment}', [PaymentController::class, 'verify'])->name('verify.action');
        Route::post('{payment}/reject', [PaymentController::class, 'reject'])->name('reject');
        Route::get('create', [PaymentController::class, 'create'])->name('create');
        Route::post('store', [PaymentController::class, 'store'])->name('store');
        Route::get('{payment}', [PaymentController::class, 'show'])->name('show');
    });

    // Customer Update Requests
Route::prefix('admin')->name('admin.')->group(function () {
    // Customer update request management
    Route::get('/customer-update-requests', [CustomerUpdateRequestController::class, 'index'])->name('customer-update-requests.index');
    Route::get('/customer-update-requests/{customerUpdateRequest}', [CustomerUpdateRequestController::class, 'show'])->name('customer-update-requests.show');
    Route::post('/customer-update-requests/{customerUpdateRequest}/approve', [CustomerUpdateRequestController::class, 'approve'])->name('customer-update-requests.approve');
    Route::post('/customer-update-requests/{customerUpdateRequest}/reject', [CustomerUpdateRequestController::class, 'reject'])->name('customer-update-requests.reject');
    
    // Field locking management
    Route::post('/customers/{customer}/unlock-fields', [CustomerUpdateRequestController::class, 'unlockFields'])->name('customers.unlock-fields');
    Route::post('/customers/{customer}/lock-fields', [CustomerUpdateRequestController::class, 'lockFields'])->name('customers.lock-fields');
    
    // Audit logs
    Route::get('/customer-audit-logs', [CustomerUpdateRequestController::class, 'auditLogs'])->name('customer-audit-logs.index');
    Route::get('/customers/{customer}/audit-logs', [CustomerUpdateRequestController::class, 'customerAuditLogs'])->name('customers.audit-logs');
});
});
// =============================================================================
// ADMIN-ONLY ROUTES
// =============================================================================


// routes/web.php
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::prefix('utilities')->group(function () {
        // Inertia Page Routes
        Route::get('/', [UtilitiesController::class, 'index'])->name('admin.utilities.index');
        
        // Form Submission Routes (redirect back)
        Route::post('/price-matrix', [UtilitiesController::class, 'updatePriceMatrix'])->name('admin.utilities.price-matrix.update');
        Route::post('/preferences', [UtilitiesController::class, 'updateUserPreferences'])->name('admin.utilities.preferences.update');
        Route::post('/backup', [UtilitiesController::class, 'createBackup'])->name('admin.utilities.backup.create');
        Route::post('/restore', [UtilitiesController::class, 'restoreBackup'])->name('admin.utilities.backup.restore');
        Route::delete('/backup', [UtilitiesController::class, 'deleteBackup'])->name('admin.utilities.backup.delete');
        Route::post('/archive', [UtilitiesController::class, 'archiveOldData'])->name('admin.utilities.archive.create');
        
        // ✅ ADDED: Download Backup Route
        Route::get('/backup/download', [UtilitiesController::class, 'downloadBackup'])->name('admin.utilities.backup.download');
        
        // API Routes (return JSON)
        Route::get('/archive/data', [UtilitiesController::class, 'getArchivedData'])->name('admin.utilities.archive.data');
        Route::post('/archive/restore', [UtilitiesController::class, 'restoreArchivedData'])->name('admin.utilities.archive.restore');
        
        // ✅ ADDED: Archive Preview Route
        Route::get('/archive/preview', [UtilitiesController::class, 'previewArchive'])->name('admin.utilities.archive.preview');
        
        // ✅ ADDED: Unified Archive Handler Route
        Route::patch('/archive', [UtilitiesController::class, 'handleArchive'])->name('admin.utilities.archive.handle');
    });
});


Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Price Matrix
    Route::get('/price-matrix/edit', [PriceMatrixController::class, 'edit'])->name('admin.price-matrix.edit');
    Route::put('/price-matrix', [PriceMatrixController::class, 'update'])->name('admin.price-matrix.update');
    Route::get('/price-matrix', [PriceMatrixController::class, 'index']);

    // Employee Management
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('admin.employees.index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('admin.employees.create');
        Route::get('/archived', [EmployeeController::class, 'archived'])->name('admin.employees.archived');
        Route::get('/generate-id', [EmployeeController::class, 'generateId'])->name('admin.employees.generate-id');
        Route::post('/', [EmployeeController::class, 'store'])->name('admin.employees.store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('admin.employees.show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('admin.employees.edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('admin.employees.update');
        Route::put('/{employee}/archive', [EmployeeController::class, 'archive'])->name('admin.employees.archive');
        Route::put('/{employee}/restore', [EmployeeController::class, 'restore'])->name('admin.employees.restore');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy');
    });

    // Customer Management
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('admin.customers.index');
        Route::get('/create', [CustomerController::class, 'create'])->name('admin.customers.create');
        Route::post('/', [CustomerController::class, 'store'])->name('admin.customers.store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('admin.customers.show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('admin.customers.edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('admin.customers.update');
        Route::put('/customers/{customer}/archive', [CustomerController::class, 'archive'])->name('admin.customers.archive');
        Route::put('/customers/{customer}/restore', [CustomerController::class, 'restore'])->name('admin.customers.restore');
        Route::get('/customers/archived', [CustomerController::class, 'archived'])->name('admin.customers.archived');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('admin.customers.destroy');
    });

    // Truck Management
    Route::prefix('trucks')->name('admin.trucks.')->group(function () {
        Route::get('/', [TruckController::class, 'index'])->name('index');
        Route::get('/create', [TruckController::class, 'create'])->name('create');
        Route::post('/', [TruckController::class, 'store'])->name('store');
        Route::get('/archived', [TruckController::class, 'archived'])->name('archived');
        Route::get('/{truck}', [TruckController::class, 'show'])->name('show');
        Route::get('/{truck}/edit', [TruckController::class, 'edit'])->name('edit');
        Route::put('/{truck}', [TruckController::class, 'update'])->name('update');
        Route::put('/{truck}/archive', [TruckController::class, 'archive'])->name('archive');
        Route::put('/{truck}/restore', [TruckController::class, 'restore'])->name('restore');
        Route::delete('/{truck}', [TruckController::class, 'destroy'])->name('destroy');

        // Maintenance
        Route::prefix('{truck}/maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [TruckMaintenanceController::class, 'index'])->name('index');
            Route::get('/create', [TruckMaintenanceController::class, 'create'])->name('create');
            Route::post('/', [TruckMaintenanceController::class, 'store'])->name('store');
            Route::get('/{maintenance}/edit', [TruckMaintenanceController::class, 'edit'])->name('edit');
            Route::put('/{maintenance}', [TruckMaintenanceController::class, 'update'])->name('update');
            Route::delete('/{maintenance}', [TruckMaintenanceController::class, 'destroy'])->name('destroy');
        });

        // Components
        Route::prefix('{truck}/components')->name('components.')->group(function () {
            Route::get('/', [TruckComponentController::class, 'index'])->name('index');
            Route::get('/create', [TruckComponentController::class, 'create'])->name('create');
            Route::post('/', [TruckComponentController::class, 'store'])->name('store');
            Route::get('/{component}', [TruckComponentController::class, 'show'])->name('show');
            Route::get('/{component}/edit', [TruckComponentController::class, 'edit'])->name('edit');
            Route::put('/{component}', [TruckComponentController::class, 'update'])->name('update');
            Route::delete('/{component}', [TruckComponentController::class, 'destroy'])->name('destroy');
        });
    });

    // Region Management
    Route::prefix('regions')->group(function () {
        Route::get('/api/regions', [RegionController::class, 'getActiveRegions']);
        Route::get('/', [RegionController::class, 'index'])->name('admin.regions.index');
        Route::get('/create', [RegionController::class, 'create'])->name('admin.regions.create');
        Route::post('/', [RegionController::class, 'store'])->name('admin.regions.store');
        Route::get('/{region}', [RegionController::class, 'show'])->name('admin.regions.show');
        Route::get('/{region}/edit', [RegionController::class, 'edit'])->name('admin.regions.edit');
        Route::put('/{region}', [RegionController::class, 'update'])->name('admin.regions.update');
        Route::put('/regions/{region}/archive', [RegionController::class, 'archive'])->name('admin.regions.archive');
        Route::put('/regions/{region}/restore', [RegionController::class, 'restore'])->name('admin.regions.restore');
        Route::get('/regions/archived', [RegionController::class, 'archived'])->name('admin.regions.archived');
        Route::delete('/regions/{region}', [RegionController::class, 'destroy'])->name('admin.regions.destroy');
    });

    // Package Management
    Route::prefix('packages')->name('admin.packages.')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::get('/{package}', [PackageController::class, 'show'])->name('show');
        Route::post('/{package}/transfer', [PackageController::class, 'transfer'])->name('transfer');
        Route::post('/{package}/status', [PackageController::class, 'updateStatus'])->name('update-status');
        Route::post('/transfers/{transfer}/arrived', [PackageController::class, 'markAsArrived'])->name('mark-arrived');
        Route::post('/bulk-status-update', [PackageController::class, 'bulkStatusUpdate'])->name('bulk-status-update');
        Route::post('/check-duplicate', [PackageController::class, 'checkDuplicate'])->name('check-duplicate');
    });
});

// =============================================================================
// DRIVER ROUTES
// =============================================================================

Route::prefix('driver')->middleware(['auth', 'role:driver'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DriverController::class, 'dashboard'])->name('driver.dashboard');

    // Package Status Management
    Route::prefix('packages')->group(function () {
        Route::get('/status-update', [DriverController::class, 'statusUpdateView'])->name('driver.status-update');
        Route::post('/bulk-update', [DriverController::class, 'bulkUpdateStatus'])->name('driver.bulk-update-status');
        Route::get('/track/{package}', [DriverController::class, 'trackPackage'])->name('driver.track-package');
        Route::post('/update-destination-status', [DriverController::class, 'updateDestinationPackagesStatus'])->name('driver.packages.update-destination-status');
        Route::post('/upload-evidence', [DriverController::class, 'uploadEvidence'])->name('driver.packages.upload-evidence');
    });

    // Delivery Management
    Route::prefix('deliveries')->group(function () {
        Route::get('/assigned', [DriverController::class, 'assignedDeliveries'])->name('driver.assigned-deliveries');
        Route::get('/{deliveryOrder}/tracking', [DriverController::class, 'deliveryTracking'])->name('driver.delivery-tracking');
    });

    // Location Management
    Route::prefix('location')->group(function () {
        Route::post('/update', [DriverController::class, 'updateDriverRegion'])->name('driver.update-region');
        Route::post('/mark-arrival', [DriverController::class, 'markArrival'])->name('driver.mark-arrival');
    });

    // Route Optimization
    Route::prefix('route')->group(function () {
        Route::get('/optimized', [DriverController::class, 'getOptimizedRoute'])->name('driver.optimized-route');
        Route::get('/with-status', [DriverController::class, 'getRouteWithStatus'])->name('driver.route-with-status');
    });

    // Cooldown and Return Management
    Route::prefix('cooldown')->group(function () {
        Route::post('/complete', [DriverController::class, 'completeCooldown'])->name('driver.complete-cooldown');
        Route::post('/skip', [DriverController::class, 'skipCooldown'])->name('driver.skip-cooldown');
        Route::post('/return-without-backhaul', [DriverController::class, 'returnWithoutBackhaul'])->name('driver.return-without-backhaul');
        Route::post('/confirm-arrival-home', [DriverController::class, 'confirmArrivalAtHome'])->name('driver.confirm-arrival-home');
    });
});

// =============================================================================
// COLLECTOR ROUTES
// =============================================================================

Route::middleware(['auth', 'role:collector'])->prefix('collector/payments')->name('collector.payments.')->group(function () {
    Route::get('/dashboard', [CollectorPaymentController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [CollectorPaymentController::class, 'index'])->name('index');
    Route::get('/pending', [CollectorPaymentController::class, 'pending'])->name('pending');
    Route::get('/create/{delivery}', [CollectorPaymentController::class, 'create'])->name('create');
    Route::post('/store/{delivery}', [CollectorPaymentController::class, 'store'])->name('store');
    Route::get('/{payment}', [CollectorPaymentController::class, 'show'])->name('show');
    Route::get('/{delivery}/{payment}/resubmit', [CollectorPaymentController::class, 'resubmit'])->name('resubmit');
    Route::put('/{delivery}/{payment}/update', [CollectorPaymentController::class, 'update'])->name('update');
    Route::delete('/{payment}', [CollectorPaymentController::class, 'destroy'])->name('destroy');
    Route::get('/{payment}/edit', [CollectorPaymentController::class, 'edit'])->name('edit');
    Route::put('/{payment}/update-payment', [CollectorPaymentController::class, 'updatePayment'])->name('update-payment');
    Route::post('/{delivery}/mark-uncollectible', [CollectorPaymentController::class, 'markUncollectible'])->name('mark-uncollectible');
});

// =============================================================================
// STAFF ROUTES
// =============================================================================

Route::middleware(['auth', 'role:staff'])->prefix('staff')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
});

// =============================================================================
// PAYMENT ROUTES
// =============================================================================

Route::prefix('payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('prepaid/{payment}', [PaymentController::class, 'showPrepaid'])->name('payments.prepaid.show');
    Route::get('collect/{payment}', [PaymentController::class, 'showCollect'])->name('payments.collect.show');
    Route::post('verify/{payment}', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('reject/{payment}', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::post('collect/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.collect.mark-paid');
    Route::post('collect/{payment}/mark-uncollectible', [PaymentController::class, 'markUncollectible'])->name('payments.collect.mark-uncollectible');
});

// =============================================================================
// PACKAGE MANAGEMENT ROUTES
// =============================================================================

Route::middleware(['auth', 'role:admin,staff'])->prefix('packages')->name('packages.')->group(function () {
    Route::get('/', [PackageController::class, 'index'])->name('index');
    Route::get('/create', [PackageController::class, 'create'])->name('create');
    Route::post('/', [PackageController::class, 'store'])->name('store');
    Route::get('/{package}', [PackageController::class, 'show'])->name('show');
    Route::get('/{package}/edit', [PackageController::class, 'edit'])->name('edit');
    Route::put('/{package}', [PackageController::class, 'update'])->name('update');
    Route::delete('/{package}', [PackageController::class, 'destroy'])->name('destroy');
    Route::post('/{package}/transfer', [PackageController::class, 'transfer'])->name('transfer');
    Route::post('/{package}/status', [PackageController::class, 'updateStatus'])->name('update-status');
    Route::post('/transfers/{transfer}/arrived', [PackageController::class, 'markAsArrived'])->name('mark-arrived');
    Route::post('/bulk-status-update', [PackageController::class, 'bulkStatusUpdate'])->name('bulk-status-update');
    Route::post('/check-duplicate', [PackageController::class, 'checkDuplicate'])->name('check-duplicate');
});

// =============================================================================
// STICKER ROUTES
// =============================================================================

Route::middleware(['auth', 'role:admin,staff'])->prefix('stickers')->name('stickers.')->group(function () {
    Route::get('/', [StickerController::class, 'index'])->name('index');
    Route::get('/statistics', [StickerController::class, 'statistics'])->name('statistics');
    Route::get('/print/{package}', [StickerController::class, 'print'])->name('print');
    Route::get('/bulk-print', [StickerController::class, 'bulkPrint'])->name('bulk-print');
    Route::get('/print-delivery-request/{deliveryRequest}', [StickerController::class, 'printForDeliveryRequest'])->name('print-delivery-request');
    Route::post('/reset/{package}', [StickerController::class, 'reset'])->name('reset');
});

// =============================================================================
// DASHBOARD ROUTES
// =============================================================================

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();
        
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('staff')) {
            return redirect()->route('staff.dashboard');
        } elseif ($user->hasRole('driver')) {
            return redirect()->route('driver.dashboard');
        } elseif ($user->hasRole('customer')) {
            return redirect()->route('customer.deliveries.index');
        } elseif ($user->hasRole('collector')) {
            return redirect()->route('collector.payments.dashboard');
        }
        
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Staff Dashboard
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
});

require __DIR__.'/auth.php';
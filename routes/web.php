<?php
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\TruckMaintenanceController;
use App\Http\Controllers\TruckComponentController;
use App\Http\Controllers\CargoAssignmentController;
use App\Http\Controllers\RequestDeliveryController;
use App\Http\Controllers\DriverTruckAssignmentController;
use App\Http\Controllers\RegionTravelDurationController;
use App\Http\Controllers\DeliveryCompletionController;
use App\Http\Controllers\Customer\TransactionController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\DeliveryRequestController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\CollectorDashboardController;
use App\Http\Controllers\Dashboard\CustomerDashboardController;
use App\Http\Controllers\Dashboard\DriverDashboardController;
use App\Http\Controllers\Dashboard\StaffDashboardController;
use App\Http\Controllers\PriceMatrixController;
use App\Http\Controllers\RequestApprovalController;
use App\Http\Controllers\AddressBookController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\WaybillController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PackageTransferController;
use App\Http\Controllers\PackageStatusHistoryController;
use App\Http\Controllers\Auth\RegisteredUserController;

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Truck;
use App\Http\Controllers\PaymentController;


// VIEW: Driver Dashboard


// Public Routes


Route::middleware(['auth', 'role:staff'])->prefix('staff')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
});

Route::get('/', fn() => Inertia::render('Customer/Home'))->name('customer.home');
Route::get('/tracking', fn() => Inertia::render('Customer/Tracking'))->name('tracking');
Route::get('/about-us', fn() => Inertia::render('Customer/AboutUs'))->name('about.us');
Route::get('/services', fn() => Inertia::render('Customer/Services'))->name('services');
Route::get('/contact-us', [ContactController::class, 'index'])->name('contact.us');
Route::post('/contact-us', [ContactController::class, 'submit'])->name('contact.submit');
Route::get('/employee', fn() => Inertia::render('Auth/EmployeeLanding'))->name('employee.landing');
Route::get('/sample', fn() => Inertia::render('ComponentsExample'))->name('sample');
Route::get('/tablesample', fn() => Inertia::render('TableSample'))->name('tablesample');

Route::prefix('api')->group(function () {
    Route::get('/delivery/regions', [RegionController::class, 'getActiveRegions']);
    
    Route::get('/price-matrix', [PriceMatrixController::class, 'index']);
    
    Route::fallback(function () {
        return response()->json([
            'message' => 'Endpoint not found'
        ], 404);
    });
});

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('customer.transactions.index');
        Route::get('/{deliveryOrder}', [TransactionController::class, 'show'])->name('customer.transactions.show');
    });


// Profile Management
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


// Customer Routes
Route::middleware(['auth', 'role:customer', 'verified'])->group(function () {
    // Address Book
    Route::get('/address-book', [AddressBookController::class, 'index'])->name('address.book');
    Route::post('/address-book', [AddressBookController::class, 'store'])->name('address.book.store');
    Route::put('/address-book/{id}', [AddressBookController::class, 'update'])->name('address.book.update');
    Route::delete('/address-book/{id}', [AddressBookController::class, 'destroy'])->name('address.book.destroy');

    // Delivery Requests
    Route::prefix('delivery-requests')->group(function () {
        Route::get('/', [DeliveryRequestController::class, 'index'])->name('customer.delivery-requests.index');
        Route::get('/create', [RequestDeliveryController::class, 'create'])->name('customer.delivery-requests.create');
        // Apply postpaid eligibility check on store route
        Route::post('/', [RequestDeliveryController::class, 'store'])
            ->name('customer.delivery-requests.store');
        Route::get('/{deliveryRequest}', [DeliveryRequestController::class, 'show'])->name('customer.delivery-requests.show');
        Route::get('/{deliveryRequest}/edit', [DeliveryRequestController::class, 'edit'])->name('customer.delivery-requests.edit');
        Route::put('/{deliveryRequest}', [DeliveryRequestController::class, 'update'])->name('customer.delivery-requests.update');
        Route::delete('/{deliveryRequest}', [DeliveryRequestController::class, 'destroy'])->name('customer.delivery-requests.destroy');

        // Separate dashboard route
        Route::get('/delivery-dashboard', [DeliveryRequestController::class, 'index'])->name('delivery-dashboard.index');

        // Calculation route
        Route::post('/calculate-price', [RequestDeliveryController::class, 'calculatePrice'])->name('customer.delivery-requests.calculate-price');
    });

    Route::put('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.markAsRead');
    Route::put('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.markAllAsRead');
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('customer.notifications.index');
});



Route::middleware(['auth', 'role:admin,staff'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/delivery', [ReportController::class, 'deliveryReport'])
        ->name('delivery');
    Route::get('/delivery/export', [ReportController::class, 'exportDeliveryReport'])
        ->name('delivery.export');
    Route::get('/financial', [ReportController::class, 'financialReport'])
        ->name('financial');
    Route::get('/financial/export', [ReportController::class, 'exportFinancialReport'])
        ->name('financial.export');
    Route::get('/manifest/{truck}', [ReportController::class, 'generateTruckManifest'])
        ->name('manifest.show')
        ->where('truck', '[0-9]+');
    Route::get('/manifest/{truck}/export', [ReportController::class, 'exportManifestReport'])
        ->name('manifest.export')
        ->where('truck', '[0-9]+');
    Route::get('/waybills', [ReportController::class, 'waybillReport'])
        ->name('waybills');
    Route::get('/waybills/export', [ReportController::class, 'exportWaybillReport'])
        ->name('waybills.export');
});



Route::middleware(['auth', 'role:admin,staff,driver'])->prefix('deliveries')->group(function () {
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


Route::prefix('admin')->middleware(['auth','role:admin,staff'])->group(function () {
    Route::get('/manifests', [ManifestController::class, 'index'])->name('manifests.index');

    Route::get('/manifests/create/{truck}', [ManifestController::class, 'create'])->name('manifests.create');
    Route::post('/manifests/{truck}', [ManifestController::class, 'store'])->name('manifests.store');

    Route::get('/manifests/{manifest}', [ManifestController::class, 'show'])->name('manifests.show');
    Route::post('/manifests/{manifest}/finalize', [ManifestController::class, 'finalize'])->name('manifests.finalize');
    Route::get('/manifests/{manifest}/print', [ManifestController::class, 'print'])->name('manifests.print');

    Route::delete('/manifests/{manifest}', [ManifestController::class, 'destroy'])->name('manifests.destroy');
});


Route::middleware(['auth', 'role:admin,staff'])->group(function () {

// 🛻 Driver-Truck Assignments
Route::put('/driver-truck-assignments/{assignment}/reactivate', 
    [DriverTruckAssignmentController::class, 'reactivate']
)->name('driver-truck-assignments.reactivate');

Route::post('/driver-truck-assignments/{assignment}/verify-return', 
    [DriverTruckAssignmentController::class, 'verifyDriverReturn']
)->name('driver-truck-assignments.verify-return');

Route::resource('driver-truck-assignments', DriverTruckAssignmentController::class)
    ->only(['index', 'store', 'destroy']);

Route::post('driver-truck-assignments/available-resources', 
    [DriverTruckAssignmentController::class, 'getAvailableResources']
)->name('driver-truck-assignments.available-resources');

Route::get('driver-truck-assignments/by-region', 
    [DriverTruckAssignmentController::class, 'getByRegion']
)->name('driver-truck-assignments.by-region');

Route::get('/driver-monitoring', [DriverTruckAssignmentController::class, 'monitor'])
    ->name('driver-monitoring.index');

});

// Allow drivers to confirm return to base
Route::middleware(['auth', 'role:driver,admin,staff'])->group(function () {
    Route::post('/driver-truck-assignments/{assignment}/confirm-return', 
        [DriverTruckAssignmentController::class, 'confirmReturnToBase']
    )->name('driver-truck-assignments.confirm-return');
});


// 📦 Cargo Assignment Management
Route::middleware(['auth', 'role:admin,staff'])->prefix('cargo-assignments')->name('cargo-assignments.')->group(function () {

    // ➡️ Basic CRUD Operations
    Route::get('/', [CargoAssignmentController::class, 'index'])->name('index');
    Route::get('/{deliveryOrder}', [CargoAssignmentController::class, 'show'])->name('show');

    // ➡️ Assignment Operations
    Route::prefix('assign')->name('assign.')->group(function () {
        Route::get('/suggestions', [CargoAssignmentController::class, 'getSuggestedAssignments'])
            ->name('suggestions');
        Route::post('/validate', [CargoAssignmentController::class, 'validateAssignment'])->name('validate');
        Route::post('/check', [CargoAssignmentController::class, 'checkAssignment'])->name('check');
        Route::post('/batch', [CargoAssignmentController::class, 'batchAssign'])->name('batch');
        Route::post('/{deliveryRequest}', [CargoAssignmentController::class, 'assign'])->name('single');
    });

    // ➡️ Delivery Operations
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::post('/{deliveryOrder}/arrival', [CargoAssignmentController::class, 'recordArrival'])->name('arrival');
        Route::post('/{deliveryOrder}/cancel', [CargoAssignmentController::class, 'cancelAssignment'])->name('cancel');
    });

    // ➡️ Dispatch Operations
    Route::prefix('dispatch')->name('dispatch.')->group(function () {
        Route::post('/driver-truck-set/{assignment}', [CargoAssignmentController::class, 'dispatchDriverTruckSet'])
            ->name('driver-truck-set');
        Route::get('/driver-truck-set/{assignment}/validate', [CargoAssignmentController::class, 'validateDispatchSet'])
            ->name('driver-truck-set.validate');
    });

    // ➡️ Truck Operations
    Route::prefix('trucks')->name('trucks.')->group(function () {
        Route::get('/{truck}/manifest', [CargoAssignmentController::class, 'generateManifest'])->name('manifest');
    });

    // ➡️ Delivery Completion Operations (moved to separate group)
    Route::prefix('delivery-completion')->name('delivery-completion.')->group(function() {
        Route::get('/ready-for-release', [DeliveryCompletionController::class, 'readyForRelease'])
            ->name('ready-for-release');
        Route::get('/{order}/release', [DeliveryCompletionController::class, 'showReleaseForm'])
            ->name('release');
        Route::post('/{order}/complete', [DeliveryCompletionController::class, 'completeOrder'])
            ->name('complete');
    });

});


Route::middleware(['auth', 'role:admin,staff'])->prefix('region-durations')->name('region-durations.')->group(function () {
    Route::get('/', [\App\Http\Controllers\RegionTravelDurationController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\RegionTravelDurationController::class, 'store'])->name('store');
    Route::put('/{region_duration}', [\App\Http\Controllers\RegionTravelDurationController::class, 'update'])->name('update');
    Route::delete('/{region_duration}', [\App\Http\Controllers\RegionTravelDurationController::class, 'destroy'])->name('destroy');
});


Route::middleware(['auth', 'role:admin,staff'])->prefix('waybills')->name('waybills.')->group(function () {
    // List all waybills
    Route::get('/', [WaybillController::class, 'index'])->name('index');

    // Show a specific waybill
    Route::get('/{waybill}', [WaybillController::class, 'show'])->name('show');

    // Generate a waybill from a delivery request
    Route::post('/generate/{deliveryRequest}', [WaybillController::class, 'generate'])->name('generate');

    // View billing page for a delivery request
    Route::get('/billing/{deliveryRequest}', [WaybillController::class, 'billing'])->name('billing');

    // Bulk generate waybills from a truck's manifest
    Route::post('/generate-from-manifest/{truck}', [WaybillController::class, 'generateFromManifest'])->name('generateFromManifest');

    // Download single waybill PDF
    Route::get('/download/{waybill}', [WaybillController::class, 'download'])->name('download');

Route::get('/waybills/{waybill}/preview', [WaybillController::class, 'preview'])
    ->name('preview');

    // Download truck's manifest PDF
     Route::get('/download-manifest/{manifest}', [WaybillController::class, 'downloadManifest'])
        ->name('downloadManifest');
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

    // Fleet
    Route::prefix('fleet')->group(function () {
        Route::get('/trucks', fn () => Inertia::render('Admin/TruckManagement'))->name('admin.trucks');
        Route::get('/truck-manifests', fn () => Inertia::render('Admin/TruckManifests'))->name('admin.truck-manifests');
        Route::get('/driver-monitoring', fn () => Inertia::render('Admin/DriverMonitor'))->name('admin.driver-monitoring');
    });

    // Operations
    Route::prefix('operations')->group(function () {
        Route::get('/items', fn () => Inertia::render('Admin/Items'))->name('admin.ItemManagement');
        Route::get('/cargo-assignments', fn () => Inertia::render('Admin/CargoAssign'))->name('admin.cargo-assignments');
        Route::get('/package-tracking', fn () => Inertia::render('Admin/PackageTrack'))->name('admin.package-tracking');
    });

    // Financial
    Route::prefix('financial')->group(function () {
        Route::get('/billing', fn () => Inertia::render('Admin/Billing'))->name('admin.billing');
        Route::get('/payment-management', fn () => Inertia::render('Admin/PaymentManagement'))->name('admin.payment-management');
        Route::get('/payment-status', fn () => Inertia::render('Admin/PaymentStatus'))->name('admin.payment-status');
        Route::get('/transaction-history', fn () => Inertia::render('TransacHistory'))->name('admin.transaction-history');
    });

    // System
    Route::prefix('system')->group(function () {
        Route::get('/backup-restore', fn () => Inertia::render('Admin/BackupRestore'))->name('admin.backup-restore');
        Route::get('/settings', fn () => Inertia::render('Admin/Settings'))->name('admin.settings');
    });


    // Package Management
Route::prefix('packages')->middleware(['auth'])->group(function () {
    Route::get('/', [PackageController::class, 'index'])->name('packages.index');
    Route::get('/{package}', [PackageController::class, 'show'])->name('packages.show');

    Route::post('/{package}/transfer', [PackageController::class, 'transfer'])->name('packages.transfer');
    Route::post('/{package}/status', [PackageController::class, 'updateStatus'])->name('packages.update-status');
    Route::post('/transfers/{transfer}/arrived', [PackageController::class, 'markAsArrived'])->name('packages.mark-arrived');
    Route::post('/check-duplicate', [PackageController::class, 'checkDuplicate'])->name('packages.check-duplicate');
});

    
});



    
  


Route::prefix('driver')->middleware(['auth', 'role:driver,staff,admin,customer'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DriverController::class, 'dashboard'])->name('driver.dashboard');

    // Package Status Management
    Route::prefix('packages')->group(function () {
        Route::get('/status-update', [DriverController::class, 'statusUpdateView'])->name('driver.status-update');
        Route::post('/bulk-update', [DriverController::class, 'bulkUpdateStatus'])->name('driver.bulk-update-status');
        Route::get('/track/{package}', [DriverController::class, 'trackPackage'])->name('driver.track-package');
    });

    // Delivery Management
    Route::prefix('deliveries')->group(function () {
        Route::get('/assigned', [DriverController::class, 'assignedDeliveries'])->name('driver.assigned-deliveries');
        Route::get('/completed', [DriverController::class, 'completedDeliveries'])->name('driver.completed-deliveries');

        // 🚛📍 New Tracking Route!
        Route::get('/{deliveryOrder}/tracking', [DriverController::class, 'deliveryTracking'])->name('driver.delivery-tracking');
    });

    // Location Management
    Route::prefix('location')->group(function () {
        Route::post('/update', [DriverController::class, 'updateDriverRegion'])->name('driver.update-region');
        Route::get('/history', [DriverController::class, 'locationHistory'])->name('driver.location-history');
    });

    // Route Optimization
    Route::prefix('route')->group(function () {
        Route::get('/optimized', [DriverController::class, 'optimizedRoute'])->name('driver.optimized-route');
    });
});


// Debug endpoints for driver/truck assignment testing
Route::get('/api/debug/drivers', function(Request $request) {
    return [
        'all_drivers_in_region' => User::where('role', 'driver')
            ->whereHas('employeeProfile', fn($q) => $q->where('region_id', $request->region_id))
            ->count(),
        'active_unassigned_drivers' => User::where('role', 'driver')
            ->where('is_active', true)
            ->whereHas('employeeProfile', fn($q) => $q
                ->where('region_id', $request->region_id)
                ->whereNull('archived_at')
            )
            ->whereDoesntHave('truckAssignments', fn($q) => $q->where('is_active', true))
            ->count()
    ];
});

Route::get('/api/debug/trucks', function(Request $request) {
    return [
        'all_trucks_in_region' => Truck::where('region_id', $request->region_id)->count(),
        'active_available_trucks' => Truck::where('region_id', $request->region_id)
            ->where('is_active', true)
            ->whereDoesntHave('driverAssignments', fn($q) => $q->where('is_active', true))
            ->whereIn('status', [
                \App\Models\Truck::STATUS_AVAILABLE,
                \App\Models\Truck::STATUS_NEARLY_FULL,
                \App\Models\Truck::STATUS_IN_USE
            ])
            ->count()
    ];
});


// Collector Payment Routes
Route::middleware(['auth', 'role:collector'])->prefix('collector/payments')->name('collector.payments.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CollectorPaymentController::class, 'index'])->name('index');
    Route::get('/pending', [\App\Http\Controllers\CollectorPaymentController::class, 'pending'])->name('pending');
    Route::get('/create/{delivery}', [\App\Http\Controllers\CollectorPaymentController::class, 'create'])->name('create');
    Route::post('/{delivery}', [\App\Http\Controllers\CollectorPaymentController::class, 'store'])->name('store');
    Route::post('/{delivery}/mark-uncollectible', [\App\Http\Controllers\CollectorPaymentController::class, 'markUncollectible'])->name('mark-uncollectible');
    Route::get('/show/{payment}', [\App\Http\Controllers\CollectorPaymentController::class, 'show'])->name('show');
    Route::delete('/{payment}', [\App\Http\Controllers\CollectorPaymentController::class, 'destroy'])->name('destroy');
    Route::get('/{payment}/edit', [\App\Http\Controllers\CollectorPaymentController::class, 'edit'])->name('edit');
    Route::put('/{payment}', [\App\Http\Controllers\CollectorPaymentController::class, 'update'])->name('update');
});

// Payment Management (Prepaid Payment Collection)
Route::middleware(['auth', 'role:admin,staff'])->prefix('staff/payments')->name('staff.payments.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PaymentController::class, 'index'])->name('index');
    Route::get('/{delivery}', [\App\Http\Controllers\PaymentController::class, 'show'])->name('show');
    Route::post('/{delivery}', [\App\Http\Controllers\PaymentController::class, 'store'])->name('store');
    Route::get('verify/{payment}', [PaymentController::class, 'verifyView'])->name('verify');
    Route::post('verify/{payment}', [PaymentController::class, 'verify'])->name('verify.action');
});

// Payment routes (for both prepaid and postpaid, with filters)
Route::prefix('payments')->group(function () {
    // Index shows both types with filters
    Route::get('/', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');

    // Prepaid specific
    Route::get('prepaid/{payment}', [\App\Http\Controllers\PaymentController::class, 'showPrepaid'])
        ->name('payments.prepaid.show');
    Route::post('prepaid/{payment}/verify', [\App\Http\Controllers\PaymentController::class, 'verify'])
        ->name('payments.prepaid.verify');

    // Postpaid specific
    Route::get('postpaid/{payment}', [\App\Http\Controllers\PaymentController::class, 'showPostpaid'])
        ->name('payments.postpaid.show');
    Route::post('postpaid/{payment}/collect', [\App\Http\Controllers\PaymentController::class, 'collect'])
        ->name('payments.postpaid.collect');
    Route::post('postpaid/{payment}/verify', [\App\Http\Controllers\PaymentController::class, 'verify'])
        ->name('payments.postpaid.verify');
});

// Customer Notifications
Route::middleware(['auth', 'role:customer', 'verified'])->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\Customer\NotificationController::class, 'index'])
        ->name('customer.notifications.index');
    Route::put('/notifications/{notification}/mark-as-read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAsRead'])
        ->name('notifications.markAsRead');
    Route::put('/notifications/mark-all-as-read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAllAsRead'])
        ->name('notifications.markAllAsRead');
});


require __DIR__.'/auth.php';

Route::middleware(['auth', 'role:collector'])->prefix('collector')->name('collector.')->group(function () {
    Route::get('dashboard', [CollectorDashboardController::class, 'index'])->name('dashboard');
});
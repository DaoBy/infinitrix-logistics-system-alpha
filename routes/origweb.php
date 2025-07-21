<?php
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\TruckMaintenanceController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\CollectorDashboardController;
use App\Http\Controllers\Dashboard\CustomerDashboardController;
use App\Http\Controllers\Dashboard\DriverDashboardController;
use App\Http\Controllers\Dashboard\StaffDashboardController;
use App\Http\Controllers\PriceMatrixController;
use App\Http\Controllers\RequestDeliveryController;
use App\Http\Controllers\AddressBookController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

//  Public Routes (Open for Everyone)
Route::get('/', fn() => Inertia::render('Customer/Home'))->name('customer.home');
Route::get('/tracking', fn() => Inertia::render('Customer/Tracking'))->name('tracking');
Route::get('/about-us', fn() => Inertia::render('Customer/AboutUs'))->name('about.us');
Route::get('/services', fn() => Inertia::render('Customer/Services'))->name('services');
Route::get('/contact-us', [ContactController::class, 'index'])->name('contact.us');
Route::post('/contact-us', [ContactController::class, 'submit'])->name('contact.submit');
Route::get('/price-matrix', [PriceMatrixController::class, 'index']);
Route::get('/employee', fn() => Inertia::render('Auth/EmployeeLanding'))->name('employee.landing'); // Redirect to Employee Login/Register needed to create a EMployee account

Route::get('/sample', fn() => Inertia::render('ComponentsExample'))->name('sample'); // Components Example 
Route::get('/tablesample', fn() => Inertia::render('TableSample'))->name('tablesample'); // table Example 






// Profile Management
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


// Walk-in Customer Route
Route::post('/walkin/store', [RegisteredUserController::class, 'storeWalkIn'])->name('walkin.store');

// Customer Routes (Authenticated: 'customer' Role)
Route::middleware(['auth', 'role:customer'])->group(function () {
    // Address Book
    Route::get('/address-book', [AddressBookController::class, 'index'])->name('address.book');
    Route::post('/address-book', [AddressBookController::class, 'store'])->name('address.book.store');
    Route::put('/address-book/{id}', [AddressBookController::class, 'update'])->name('address.book.update');
    Route::delete('/address-book/{id}', [AddressBookController::class, 'destroy'])->name('address.book.destroy');
    
    // Transaction History
    Route::get('/transaction-history', [TransactionHistoryController::class, 'index'])->name('transaction.history');
    
    // Request Delivery
    Route::get('/request-delivery', fn() => inertia('Customer/RequestDelivery'))->name('request.delivery');
    Route::post('/request-delivery', [RequestDeliveryController::class, 'store'])->name('request.delivery.store');
    Route::get('/customer/delivery-requests', fn() => inertia('Customer/DeliveryRequests'))->name('customer.delivery.requests');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', fn () => Inertia::render('Admin/AdminDash'))->name('admin.dashboard');
    
    // Employee Management Routes
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('admin.employees.index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('admin.employees.create');
        Route::post('/', [EmployeeController::class, 'store'])->name('admin.employees.store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('admin.employees.show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('admin.employees.edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('admin.employees.update');
        Route::put('/{employee}/archive', [EmployeeController::class, 'archive'])->name('admin.employees.archive');
        Route::put('/{employee}/restore', [EmployeeController::class, 'restore'])->name('admin.employees.restore');
        Route::get('/archived', [EmployeeController::class, 'archived'])->name('admin.employees.archived');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy');
    });

    // Customer Management Routes
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('admin.customers.index');
        Route::get('/create', [CustomerController::class, 'create'])->name('admin.customers.create');
        Route::post('/', [CustomerController::class, 'store'])->name('admin.customers.store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('admin.customers.show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('admin.customers.edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('admin.customers.update');
        Route::put('/{customer}/archive', [CustomerController::class, 'archive'])->name('admin.customers.archive');
        Route::put('/{customer}/restore', [CustomerController::class, 'restore'])->name('admin.customers.restore');
        Route::get('/archived', [CustomerController::class, 'archived'])->name('admin.customers.archived');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('admin.customers.destroy');
    });

    // Truck Management Routes
    Route::prefix('trucks')->group(function () {
        Route::get('/', [TruckController::class, 'index'])->name('admin.trucks.index');
        Route::get('/create', [TruckController::class, 'create'])->name('admin.trucks.create');
        Route::post('/', [TruckController::class, 'store'])->name('admin.trucks.store');
        Route::get('/{truck}', [TruckController::class, 'show'])->name('admin.trucks.show');
        Route::get('/{truck}/edit', [TruckController::class, 'edit'])->name('admin.trucks.edit');
        Route::put('/{truck}', [TruckController::class, 'update'])->name('admin.trucks.update');
        Route::put('/{truck}/archive', [TruckController::class, 'archive'])->name('admin.trucks.archive');
        Route::put('/{truck}/restore', [TruckController::class, 'restore'])->name('admin.trucks.restore');
        Route::get('/archived', [TruckController::class, 'archived'])->name('admin.trucks.archived');
        Route::delete('/{truck}', [TruckController::class, 'destroy'])->name('admin.trucks.destroy');

        // Maintenance Subroutes
        Route::prefix('{truck}/maintenance')->group(function () {
            Route::post('/', [TruckMaintenanceController::class, 'store'])->name('admin.trucks.maintenance.store');
            Route::put('/{maintenance}', [TruckMaintenanceController::class, 'update'])->name('admin.trucks.maintenance.update');
            Route::delete('/{maintenance}', [TruckMaintenanceController::class, 'destroy'])->name('admin.trucks.maintenance.destroy');
        });
    });

    // Region Management Routes
    Route::prefix('regions')->group(function () {
        Route::get('/', [RegionController::class, 'index'])->name('admin.regions.index');
        Route::get('/create', [RegionController::class, 'create'])->name('admin.regions.create');
        Route::post('/', [RegionController::class, 'store'])->name('admin.regions.store');
        Route::get('/{region}', [RegionController::class, 'show'])->name('admin.regions.show');
        Route::get('/{region}/edit', [RegionController::class, 'edit'])->name('admin.regions.edit');
        Route::put('/{region}', [RegionController::class, 'update'])->name('admin.regions.update');
        Route::put('/{region}/archive', [RegionController::class, 'archive'])->name('admin.regions.archive');
        Route::put('/{region}/restore', [RegionController::class, 'restore'])->name('admin.regions.restore');
        Route::get('/archived', [RegionController::class, 'archived'])->name('admin.regions.archived');
        Route::delete('/{region}', [RegionController::class, 'destroy'])->name('admin.regions.destroy');
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
});

// Staff Routes
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
});

// Driver Routes
Route::middleware(['auth', 'role:driver'])->group(function () {
    Route::get('/driver/dashboard', [DriverDashboardController::class, 'index'])->name('driver.dashboard');
});

// Collector Routes
Route::middleware(['auth', 'role:collector'])->group(function () {
    Route::get('/collector/dashboard', [CollectorDashboardController::class, 'index'])->name('collector.dashboard');
    Route::get('/collector/payments', fn() => Inertia::render('Collector/CConfirmedPayments'))->name('collector.payments'); 
    Route::get('/collector/verify', fn() => Inertia::render('Collector/CPaymentStatus'))->name('collector.verify'); 
    Route::get('/collector/history', fn() => Inertia::render('Collector/CTransactionHistory'))->name('collector.history'); 

});

require __DIR__.'/auth.php';

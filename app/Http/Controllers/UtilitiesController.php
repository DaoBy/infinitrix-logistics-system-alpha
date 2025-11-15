<?php

namespace App\Http\Controllers;

use App\Models\PackageCategory;
use App\Models\PriceMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class UtilitiesController extends Controller
{
    public function index()
    {
        $priceMatrix = PriceMatrix::first();
        $backups = $this->listBackups();
        $packageCategories = PackageCategory::ordered()->get();
        
        return Inertia::render('Admin/Utilities/Index', [
            'priceMatrix' => $priceMatrix,
            'userPreferences' => $this->getUserPreferences(),
            'backups' => $backups,
            'packageCategories' => $packageCategories,
        ]);
    }

    // Package Category Management Methods
   public function getPackageCategories()
{
    $categories = PackageCategory::ordered()->get()->map(function ($category) {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'code' => $category->code,
            'description' => $category->description,
            'dimensions' => $category->dimensions,
            'image_url' => $category->image_url, // Add this line
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order
        ];
    });
    
    return response()->json([
        'categories' => $categories
    ]);
}

    public function storePackageCategory(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:10|',
        'description' => 'nullable|string',
        'length' => 'nullable|numeric|min:0',
        'height' => 'nullable|numeric|min:0',
        'width' => 'nullable|numeric|min:0',
        'is_active' => 'boolean',
        'sort_order' => 'nullable|integer'
    ]);

    // Handle image upload
    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('package-categories', 'public');
    }

    $category = PackageCategory::create([
        'name' => $validated['name'],
        'code' => $validated['code'], // REMOVED: strtoupper()
        'description' => $validated['description'] ?? null,
        'image' => $imagePath,
        'dimensions' => [
            'length' => $validated['length'] ?? null,
            'height' => $validated['height'] ?? null,
            'width' => $validated['width'] ?? null,
        ],
        'is_active' => $validated['is_active'] ?? true,
        'sort_order' => $validated['sort_order'] ?? 0
    ]);

    return redirect()->route('admin.utilities.index')->with('success', 'Package category created successfully.');
}


    public function updatePackageCategory(Request $request, PackageCategory $packageCategory)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:10|',
        'description' => 'nullable|string',
        'length' => 'nullable|numeric|min:0',
        'height' => 'nullable|numeric|min:0',
        'width' => 'nullable|numeric|min:0',
        'is_active' => 'boolean',
    ]);

    // Handle image upload
    if ($request->hasFile('image')) {
        // Delete old image if exists
        if ($packageCategory->image) {
            Storage::disk('public')->delete($packageCategory->image);
        }
        $imagePath = $request->file('image')->store('package-categories', 'public');
        $validated['image'] = $imagePath;
    }

    $packageCategory->update([
        'name' => $validated['name'],
        'code' => $validated['code'], // REMOVED: strtoupper()
        'description' => $validated['description'] ?? null,
        'dimensions' => [
            'length' => $validated['length'] ?? null,
            'height' => $validated['height'] ?? null,
            'width' => $validated['width'] ?? null,
        ],
        'is_active' => $validated['is_active'] ?? true,
    ]);

    // Update image if new one was uploaded
    if (isset($validated['image'])) {
        $packageCategory->update(['image' => $validated['image']]);
    }

    return redirect()->route('admin.utilities.index')->with('success', 'Package category updated successfully.');
}

   public function deletePackageCategory(PackageCategory $packageCategory)
{
    // Check if category is being used by any packages
    $packageCount = \App\Models\Package::where('category', $packageCategory->code)->count();
    
    if ($packageCount > 0) {
        return redirect()->route('admin.utilities.index')->with('error', 'Cannot delete category. It is being used by ' . $packageCount . ' packages.');
    }

    // Delete image if exists
    if ($packageCategory->image) {
        Storage::disk('public')->delete($packageCategory->image);
    }

    $packageCategory->delete();

    return redirect()->route('admin.utilities.index')->with('success', 'Package category deleted successfully.');
}


    public function updatePackageCategoryOrder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:package_categories,id',
            'categories.*.sort_order' => 'required|integer'
        ]);

        foreach ($request->categories as $category) {
            PackageCategory::where('id', $category['id'])->update([
                'sort_order' => $category['sort_order']
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category order updated successfully.'
        ]);
    }

    // Price Matrix Methods
    public function updatePriceMatrix(Request $request)
    {
        $validated = $request->validate([
            'base_fee' => 'required|numeric|min:0',
            'volume_rate' => 'required|numeric|min:0',
            'weight_rate' => 'required|numeric|min:0',
            'package_rate' => 'required|numeric|min:0',
        ]);

        $priceMatrix = PriceMatrix::firstOrNew([]);
        $priceMatrix->fill($validated);
        $priceMatrix->save();

        return redirect()->route('admin.utilities.index')->with('success', 'Price matrix updated successfully.');
    }

    // User Preferences Methods
    public function getUserPreferences()
    {
        return [
            'font_style' => session('font_style', 'inter'),
            'font_size' => session('font_size', 'medium'),
            'dark_mode' => session('dark_mode', false),
        ];
    }

    public function updateUserPreferences(Request $request)
    {
        $validated = $request->validate([
            'font_style' => 'required|string|in:inter,arial,roboto',
            'font_size' => 'required|string|in:small,medium,large',
        ]);

        foreach ($validated as $key => $value) {
            session([$key => $value]);
        }

        return redirect()->route('admin.utilities.index')->with('success', 'Preferences updated successfully.');
    }

    // Backup Methods
    public function createBackup()
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sqlite";
            
            $databasePath = database_path('database.sqlite');
            $backupPath = storage_path("app/backups/{$filename}");
            
            if (!file_exists(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }
            
            if (!file_exists($databasePath)) {
                return redirect()->route('admin.utilities.index')->with('error', 'Database file not found.');
            }
            
            copy($databasePath, $backupPath);
            
            return redirect()->route('admin.utilities.index')->with('success', "Backup created: {$filename}");
            
        } catch (\Exception $e) {
            return redirect()->route('admin.utilities.index')->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function listBackups()
    {
        $backupPath = storage_path('app/backups');
        $backups = [];
        
        if (file_exists($backupPath)) {
            $files = scandir($backupPath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sqlite') {
                    $filePath = $backupPath . '/' . $file;
                    $backups[] = [
                        'filename' => $file,
                        'size' => round(filesize($filePath) / 1024, 2) . ' KB',
                        'created_at' => date('Y-m-d H:i:s', filemtime($filePath))
                    ];
                }
            }
        }
        
        return array_reverse($backups);
    }

    public function downloadBackup(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        try {
            $backupPath = storage_path("app/backups/{$request->filename}");
            
            if (!file_exists($backupPath)) {
                return response()->json(['error' => 'Backup file not found.'], 404);
            }

            $fileSize = filesize($backupPath);
            
            $headers = [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $request->filename . '"',
                'Content-Length' => $fileSize,
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            return response()->file($backupPath, $headers);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Download failed: ' . $e->getMessage()], 500);
        }
    }

    public function restoreBackup(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        try {
            $backupPath = storage_path("app/backups/{$request->filename}");
            $databasePath = database_path('database.sqlite');
            
            if (!file_exists($backupPath)) {
                return redirect()->route('admin.utilities.index')->with('error', 'Backup file not found.');
            }
            
            if (!file_exists($databasePath)) {
                return redirect()->route('admin.utilities.index')->with('error', 'Database file not found.');
            }
            
            $tempBackup = storage_path("app/backups/temp_pre_restore_" . now()->format('Y-m-d_H-i-s') . ".sqlite");
            copy($databasePath, $tempBackup);
            
            copy($backupPath, $databasePath);
            
            return redirect()->route('admin.utilities.index')->with('success', 'Database restored successfully. Temporary backup created: ' . basename($tempBackup));
            
        } catch (\Exception $e) {
            return redirect()->route('admin.utilities.index')->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    public function deleteBackup(Request $request)
    {
        $request->validate([
            'filename' => 'required|string'
        ]);

        try {
            $backupPath = storage_path("app/backups/{$request->filename}");
            
            if (!file_exists($backupPath)) {
                return redirect()->route('admin.utilities.index')->with('error', 'Backup file not found.');
            }
            
            unlink($backupPath);
            
            return redirect()->route('admin.utilities.index')->with('success', 'Backup deleted successfully.');
            
        } catch (\Exception $e) {
            return redirect()->route('admin.utilities.index')->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    // Archive Methods
    public function archiveOldData(Request $request)
    {
        $request->validate([
            'archive_type' => 'required|in:manifests,waybills',
            'older_than_days' => 'required|integer|min:1'
        ]);

        try {
            $dateThreshold = now()->subDays($request->older_than_days);
            $tableName = $request->archive_type;
            
            if (!Schema::hasColumn($tableName, 'archived_at')) {
                return redirect()->route('admin.utilities.index')->with('error', "Archive feature not setup for {$tableName}. Please run migrations.");
            }
            
            $affected = DB::table($tableName)
                ->where('created_at', '<', $dateThreshold)
                ->whereNull('archived_at')
                ->update(['archived_at' => now()]);
            
            return redirect()->route('admin.utilities.index')->with('success', "Archived {$affected} {$request->archive_type} records.");
            
        } catch (\Exception $e) {
            return redirect()->route('admin.utilities.index')->with('error', 'Archive failed: ' . $e->getMessage());
        }
    }

    public function previewArchive(Request $request)
    {
        $request->validate([
            'archive_type' => 'required|in:manifests,waybills',
            'days' => 'required|integer|min:1'
        ]);

        try {
            $dateThreshold = now()->subDays($request->days);
            $tableName = $request->archive_type;
            
            $count = DB::table($tableName)
                ->where('created_at', '<', $dateThreshold)
                ->whereNull('archived_at')
                ->count();

            return response()->json([
                'count' => $count,
                'message' => "Found {$count} records to archive"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'count' => 0,
                'error' => 'Preview failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getArchivedData(Request $request)
    {
        $request->validate([
            'archive_type' => 'required|in:manifests,waybills'
        ]);

        try {
            $tableName = $request->archive_type;
            
            if (!Schema::hasColumn($tableName, 'archived_at')) {
                return response()->json([
                    'archivedData' => [],
                    'type' => $request->archive_type
                ]);
            }
            
            $query = DB::table($tableName)
                ->whereNotNull('archived_at')
                ->orderBy('archived_at', 'desc');

            if ($tableName === 'manifests') {
                $data = $query->get()->map(function ($manifest) {
                    $manifestModel = \App\Models\Manifest::with(['driver', 'truck'])->find($manifest->id);
                    
                    return [
                        'id' => $manifest->id,
                        'manifest_number' => $manifest->manifest_number,
                        'status' => $manifest->status,
                        'notes' => $manifest->notes,
                        'created_at' => $manifest->created_at,
                        'archived_at' => $manifest->archived_at,
                        'driver' => $manifestModel->driver ? ['name' => $manifestModel->driver->name] : null,
                        'truck' => $manifestModel->truck ? [
                            'plate_number' => $manifestModel->truck->license_plate ?? 'Not assigned',
                            'model' => $manifestModel->truck->model ?? 'N/A'
                        ] : ['plate_number' => 'Not assigned', 'model' => 'N/A'],
                    ];
                });
            } else if ($tableName === 'waybills') {
                $data = $query->get()->map(function ($waybill) {
                    $waybillModel = \App\Models\Waybill::with(['generator'])->find($waybill->id);
                    
                    return [
                        'id' => $waybill->id,
                        'waybill_number' => $waybill->waybill_number,
                        'notes' => $waybill->notes,
                        'created_at' => $waybill->created_at,
                        'archived_at' => $waybill->archived_at,
                        'generator' => $waybillModel->generator ? ['name' => $waybillModel->generator->name] : null,
                    ];
                });
            } else {
                $data = $query->get();
            }

            return response()->json([
                'archivedData' => $data,
                'type' => $request->archive_type
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getArchivedData: ' . $e->getMessage());
            return response()->json([
                'archivedData' => [],
                'type' => $request->archive_type,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restoreArchivedData(Request $request)
    {
        \Log::info('Restore request received', $request->all());
        
        $request->validate([
            'archive_type' => 'required|in:manifests,waybills',
            'id' => 'required|integer'
        ]);

        try {
            $tableName = $request->archive_type;
            
            \Log::info('Attempting to restore', [
                'table' => $tableName,
                'id' => $request->id
            ]);
            
            if (!Schema::hasColumn($tableName, 'archived_at')) {
                \Log::error('Archive column missing', ['table' => $tableName]);
                return back()->with('error', 'Archive feature not setup.');
            }
            
            $affected = DB::table($tableName)
                ->where('id', $request->id)
                ->update(['archived_at' => null]);
            
            \Log::info('Update result', ['affected' => $affected]);
            
            if ($affected) {
                return back()->with('success', 'Record restored successfully.');
            } else {
                \Log::warning('Record not found', ['id' => $request->id]);
                return back()->with('error', 'Record not found.');
            }
            
        } catch (\Exception $e) {
            \Log::error('Restore failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    public function handleArchive(Request $request)
    {
        \Log::info('Handle Archive request received', $request->all());
        
        if ($request->action === 'restore') {
            return $this->restoreArchivedData($request);
        }
        
        return $this->archiveOldData($request);
    }
}
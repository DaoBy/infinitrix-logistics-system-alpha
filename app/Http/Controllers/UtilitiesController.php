<?php

namespace App\Http\Controllers;

use App\Models\PriceMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // ✅ ADD THIS LINE
use Inertia\Inertia;

class UtilitiesController extends Controller
{
    // Main utilities page - combines price matrix, preferences, and backups
    public function index()
    {
        $priceMatrix = PriceMatrix::first();
        $backups = $this->listBackups();
        
        return Inertia::render('Admin/Utilities/Index', [
            'priceMatrix' => $priceMatrix,
            'userPreferences' => $this->getUserPreferences(),
            'backups' => $backups,
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

    // Add this method to your UtilitiesController
// Add this method to your UtilitiesController
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

        // Get file size for headers
        $fileSize = filesize($backupPath);
        
        // Set headers for file download
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $request->filename . '"',
            'Content-Length' => $fileSize,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        // Return file response
        return response()->file($backupPath, $headers);
        
    } catch (\Exception $e) {
        return response()->json(['error' => 'Download failed: ' . $e->getMessage()], 500);
    }
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
            
            // Copy SQLite database file
            $databasePath = database_path('database.sqlite');
            $backupPath = storage_path("app/backups/{$filename}");
            
            // Ensure backup directory exists
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

    // Recovery Methods
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
            
            // Create temporary backup before restore (safety measure)
            $tempBackup = storage_path("app/backups/temp_pre_restore_" . now()->format('Y-m-d_H-i-s') . ".sqlite");
            copy($databasePath, $tempBackup);
            
            // Perform the restore
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

    // Archive Methods (Manifests & Waybills Only)
    public function archiveOldData(Request $request)
    {
        $request->validate([
            'archive_type' => 'required|in:manifests,waybills',
            'older_than_days' => 'required|integer|min:1'
        ]);

        try {
            $dateThreshold = now()->subDays($request->older_than_days);
            $tableName = $request->archive_type;
            
            // Check if table has archived_at column
            if (!\Schema::hasColumn($tableName, 'archived_at')) {
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
   // ✅ ADDED: Archive Preview Method
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

        // Better way to handle relationships - use model relationships
    if ($tableName === 'manifests') {
    // Select all manifest columns and add relationship data
    $data = $query->get()->map(function ($manifest) {
        // Load relationships using models
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
                'plate_number' => $manifestModel->truck->license_plate ?? 'Not assigned', // ✅ FIXED: license_plate instead of plate_number
                'model' => $manifestModel->truck->model ?? 'N/A'
            ] : ['plate_number' => 'Not assigned', 'model' => 'N/A'],
        ];
    });
} else if ($tableName === 'waybills') {
            // Select all waybill columns and add relationship data
            $data = $query->get()->map(function ($waybill) {
                // Load relationships using models
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
    // This should also be an API endpoint
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
    
    // Default to archive action
    return $this->archiveOldData($request);
}
}
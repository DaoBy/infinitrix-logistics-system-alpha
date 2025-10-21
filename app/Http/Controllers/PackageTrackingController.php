<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageTrackingController extends Controller
{
    /**
     * Show tracking landing page for public users
     */
    public function showTrackingLanding()
    {
        return inertia('Shared/PackageTracking/PublicTrackingLanding');
    }

    /**
     * Public package tracking with flexible item code matching and proper error handling
     */
    public function publicTrackPackage($itemCode)
    {
        // Clean and normalize the input
        $cleanCode = strtoupper(trim($itemCode));
        
        // Remove common prefixes and dashes for flexible matching
        $cleanCode = str_replace(['PKG-', 'TRK-', 'SHIP-', '-', ' '], '', $cleanCode);
        
        \Log::info('Package tracking search', [
            'original_input' => $itemCode,
            'cleaned_code' => $cleanCode,
            'search_type' => 'public_tracking'
        ]);

        // Validate input length
        if (strlen($cleanCode) < 4) {
            return inertia('Shared/PackageTracking/PublicTrackingLanding', [
                'error' => 'Tracking code must be at least 4 characters long. Please check your code and try again.',
                'searchedCode' => $itemCode,
            ]);
        }

        // Validate characters (only letters and numbers allowed)
        if (!preg_match('/^[A-Z0-9]+$/', $cleanCode)) {
            return inertia('Shared/PackageTracking/PublicTrackingLanding', [
                'error' => 'Invalid tracking code format. Only letters and numbers are allowed.',
                'searchedCode' => $itemCode,
            ]);
        }

        // Smart search: try multiple matching strategies
        $package = Package::where(function($query) use ($cleanCode, $itemCode) {
            // 1. Exact match with original input
            $query->where('item_code', $itemCode);
            
            // 2. Exact match with cleaned code
            $query->orWhere('item_code', $cleanCode);
            
            // 3. Match end of item_code (last 6+ characters)
            if (strlen($cleanCode) >= 6) {
                $query->orWhere('item_code', 'like', '%' . substr($cleanCode, -6));
            }
            
            // 4. Match with dashes included
            $query->orWhere('item_code', 'like', '%' . str_replace('-', '', $cleanCode));
            
            // 5. For short codes, be more flexible
            if (strlen($cleanCode) >= 4 && strlen($cleanCode) <= 8) {
                $query->orWhere('item_code', 'like', '%-' . $cleanCode)
                      ->orWhere('item_code', 'like', $cleanCode . '-%');
            }
            
            // 6. Case-insensitive search
            $query->orWhereRaw('UPPER(item_code) = ?', [strtoupper($itemCode)])
                  ->orWhereRaw('UPPER(item_code) = ?', [strtoupper($cleanCode)]);
        })->first();

        if (!$package) {
            \Log::warning('Package not found for tracking', [
                'search_input' => $itemCode,
                'cleaned_input' => $cleanCode
            ]);
            
            // Return back to landing page with error
            return inertia('Shared/PackageTracking/PublicTrackingLanding', [
                'error' => 'Package not found. Please check your tracking code and try again.',
                'searchedCode' => $itemCode,
            ]);
        }

        \Log::info('Package found for tracking', [
            'package_id' => $package->id,
            'item_code' => $package->item_code,
            'searched_with' => $itemCode
        ]);

        // Load all relationships
        $package->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.dropOffRegion',
            'currentRegion',
            'statusHistory' => function($query) {
                $query->with('updatedBy')->latest();
            },
            'transfers' => function($query) {
                $query->with(['fromRegion', 'toRegion', 'processor'])->latest();
            }
        ]);

        return inertia('Shared/PackageTracking/PublicPackageTracking', [
            'package' => $package,
            'statusHistory' => $package->statusHistory,
            'transfers' => $package->transfers,
            'searchedCode' => $itemCode, // Pass what user searched for
            'actualCode' => $package->item_code, // Pass actual tracking number found
        ]);
    }

    /**
     * Staff/Driver package tracking (authenticated)
     */
    public function trackPackage(Package $package)
    {
        // Optional: Add authorization if needed
        // $this->authorize('view', $package);

        $package->load([
            'statusHistory' => function($query) {
                $query->latest()->with('updatedBy');
            },
            'transfers' => function($query) {
                $query->latest()->with(['fromRegion', 'toRegion', 'processor']);
            },
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.dropOffRegion',
            'currentRegion'
        ]);

        return inertia('Shared/PackageTracking/PackageTracking', [
            'package' => $package,
            'statusHistory' => $package->statusHistory,
            'transfers' => $package->transfers,
        ]);
    }
}
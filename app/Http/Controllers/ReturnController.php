<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackageTransfer;

class ReturnController extends Controller
{
    public function initiateReturn(Package $package)
    {
        DB::transaction(function () use ($package) {
            $transfer = $package->transferToRegion(
                $package->deliveryRequest->pick_up_branch,
                auth()->user(),
                'Return initiated',
                true // Mark as return
            );
            
            $package->update(['status' => 'returned']);
        });

        return back()->with('success', 'Return initiated!');
    }

    public function confirmReturn(PackageTransfer $transfer)
    {
        $transfer->package->update([
            'status' => 'return_completed'
        ]);

        return back()->with('success', 'Return completed!');
    }
}
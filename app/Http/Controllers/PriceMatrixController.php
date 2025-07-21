<?php

namespace App\Http\Controllers;

use App\Models\PriceMatrix;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PriceMatrixController extends Controller
{
    // Show edit form
    public function edit()
    {
        $priceMatrix = PriceMatrix::firstOrCreate([], [
            'base_fee' => 50.00,
            'volume_rate' => 10.00,
            'weight_rate' => 5.00,
            'package_rate' => 2.00
        ]);
        
        return Inertia::render('Admin/PriceMatrix/Edit', [
            'priceMatrix' => $priceMatrix,
        ]);
    }

    // Update price matrix
    public function update(Request $request)
    {
        $validated = $request->validate([
            'base_fee' => 'required|numeric|min:50|max:1000', // Example reasonable range
            'volume_rate' => 'required|numeric|min:5|max:50',
            'weight_rate' => 'required|numeric|min:1|max:20',
            'package_rate' => 'required|numeric|min:0.5|max:10',
        ]);

        $priceMatrix = PriceMatrix::first();
        $priceMatrix->update($validated);

        return redirect()->back()
            ->with('success', 'Price matrix updated successfully!');
    }

    // API endpoint for getting prices
    public function index()
    {
        $matrix = PriceMatrix::firstOrCreate([], [
            'base_fee' => 50.00,
            'volume_rate' => 10.00,
            'weight_rate' => 5.00,
            'package_rate' => 2.00
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $matrix
        ]);
    }
}
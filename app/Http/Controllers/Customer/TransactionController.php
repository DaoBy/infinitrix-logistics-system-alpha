<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $query = DeliveryOrder::with(['deliveryRequest.receiver']);

        // Apply filters
        if ($request->filled('search')) {
            $query->whereHas('deliveryRequest.receiver', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('company_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->where('delivery_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('delivery_date', '<=', $request->date_to);
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'delivery_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $transactions = $query->paginate(10)->withQueryString();

        return Inertia::render('Customer/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'sort_field', 'sort_direction']),
        ]);
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['deliveryRequest.receiver']);
        return Inertia::render('Customer/Transactions/Show', [
            'transaction' => $deliveryOrder,
        ]);
    }
}
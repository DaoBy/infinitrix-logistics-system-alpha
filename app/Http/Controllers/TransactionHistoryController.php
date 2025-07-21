<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionHistoryController extends Controller
{
    /**
     * Display the transaction history page.
     */
    public function index()
    {
        // Fetch transactions from the database (example data)
        $transactions = [
            [
                'id' => 1,
                'date' => '2023-10-01',
                'description' => 'Package Delivery',
                'amount' => '$25.00',
                'status' => 'Completed',
            ],
            [
                'id' => 2,
                'date' => '2023-09-28',
                'description' => 'Express Delivery',
                'amount' => '$40.00',
                'status' => 'Completed',
            ],
            [
                'id' => 3,
                'date' => '2023-09-25',
                'description' => 'Standard Delivery',
                'amount' => '$15.00',
                'status' => 'Cancelled',
            ],
        ];

        return Inertia::render('Customer/TransactionHistory', [
            'transactions' => $transactions,
        ]);
    }
}
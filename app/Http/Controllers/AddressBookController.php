<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AddressBookController extends Controller
{
    /**
     * Display the address book page.
     */
    public function index()
    {
        // Fetch addresses from the database (example data)
        $addresses = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'street' => '123 Main St',
                'city' => 'New York',
                'province' => 'NY',
                'zip' => '10001',
                'isDefault' => true,
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'street' => '456 Elm St',
                'city' => 'Los Angeles',
                'province' => 'CA',
                'zip' => '90001',
                'isDefault' => false,
            ],
        ];

        return Inertia::render('Customer/AddressBook', [
            'addresses' => $addresses,
        ]);
    }

    /**
     * Store a new address in the address book.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'zip' => 'required|string|max:10',
            'isDefault' => 'boolean',
        ]);

        // Save the address to the database (example logic)
        // Address::create($validated);

        return redirect()->route('address.book')->with('success', 'Address added successfully!');
    }

    /**
     * Update an existing address in the address book.
     */
    public function update(Request $request, $id)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'zip' => 'required|string|max:10',
            'isDefault' => 'boolean',
        ]);

        // Update the address in the database (example logic)
        // Address::find($id)->update($validated);

        return redirect()->route('address.book')->with('success', 'Address updated successfully!');
    }

    /**
     * Delete an address from the address book.
     */
    public function destroy($id)
    {
        // Delete the address from the database (example logic)
        // Address::find($id)->delete();

        return redirect()->route('address.book')->with('success', 'Address deleted successfully!');
    }
}
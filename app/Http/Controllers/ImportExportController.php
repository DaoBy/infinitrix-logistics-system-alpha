<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use League\Csv\Writer;
use SplTempFileObject;

class ImportExportController extends Controller
{
    public function exportCustomers()
    {
        $customers = Customer::all();
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        
        $csv->insertOne([
            'ID', 'Name', 'Company', 'Email', 'Phone', 'Address'
        ]);

        foreach ($customers as $customer) {
            $csv->insertOne([
                $customer->id,
                $customer->name,
                $customer->company_name,
                $customer->email,
                $customer->mobile,
                $customer->full_address
            ]);
        }

        $csv->output('customers_' . date('Y-m-d') . '.csv');
        die;
    }

    public function importCustomers(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $csv = Reader::createFromPath($file->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $record) {
            Customer::updateOrCreate(
                ['email' => $record['Email']],
                [
                    'first_name' => $record['Name'],
                    'company_name' => $record['Company'],
                    'mobile' => $record['Phone'],
                    // ... other fields
                ]
            );
        }

        return back()->with('success', 'Customers imported successfully!');
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class UpdateCustomerDeliveryHistory extends Command
{
    protected $signature = 'customers:update-delivery-status';
    protected $description = 'Update delivery status and field locking for all customers';

    public function handle()
    {
        $customers = Customer::with(['sentDeliveries', 'receivedDeliveries'])->get();
        $bar = $this->output->createProgressBar($customers->count());

        $this->info('Updating customer delivery status...');

        $activeCount = 0;
        $historyCount = 0;
        $lockedCount = 0;

        foreach ($customers as $customer) {
            // Calculate statuses
            $hasHistory = $customer->hasDeliveryHistory();
            $hasActiveOrUnpaid = $customer->hasActiveOrUnpaidDeliveries();
            
            // Update the database fields
            $customer->update([
                'has_delivery_history' => $hasHistory,
                'critical_fields_locked' => $hasHistory
            ]);
            
            if ($hasActiveOrUnpaid) $activeCount++;
            if ($hasHistory) {
                $historyCount++;
                $lockedCount++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Completed updating delivery status for {$customers->count()} customers.");
        $this->info("{$activeCount} customers have active/unpaid deliveries (cannot request changes).");
        $this->info("{$historyCount} customers have delivery history.");
        $this->info("{$lockedCount} customers have critical fields locked.");
        
        return Command::SUCCESS;
    }
}
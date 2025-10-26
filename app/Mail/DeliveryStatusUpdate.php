<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeliveryStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $deliveryOrder;
    public $status;
    public $recipientType;

    /**
     * Create a new message instance.
     */
    public function __construct($deliveryOrder, $status, $recipientType = 'receiver')
    {
        $this->deliveryOrder = $deliveryOrder;
        $this->status = $status;
        $this->recipientType = $recipientType;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->getSubject();
        
        return $this->subject($subject)
                    ->view('emails.delivery-status-update')
                    ->with([
                        'deliveryOrder' => $this->deliveryOrder,
                        'status' => $this->status,
                        'recipientType' => $this->recipientType,
                    ]);
    }

    private function getSubject()
    {
        $reference = $this->deliveryOrder->deliveryRequest->reference_number ?? 'Unknown';
        
        if ($this->status === 'delivered') {
            return "Your Delivery #{$reference} Has Been Delivered - Ready for Pickup";
        } else {
            return "Action Required: Your Delivery #{$reference} Needs Review";
        }
    }
}
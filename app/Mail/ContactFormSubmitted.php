<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $contactData;

    /**
     * Create a new message instance.
     */
    public function __construct($contactData)
    {
        $this->contactData = $contactData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->from('noreply@infinitrix.work', 'Infinitrix Logistics')
            ->subject('New Contact Form Submission: ' . $this->contactData['subject'])
            ->to('infinitrixexpress@gmail.com')
            ->replyTo($this->contactData['email'])
            ->view('emails.contact_form')
            ->with(['data' => $this->contactData]);
    }
}
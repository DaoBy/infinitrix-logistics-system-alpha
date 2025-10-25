<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Mail\ContactFormSubmitted;

class ContactController extends Controller
{
    /**
     * Display the contact us page.
     */
    public function index()
    {
        return Inertia::render('Customer/ContactUs');
    }

    /**
     * Handle the contact form submission.
     */
    public function submit(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        Log::info('Contact form submitted', $validated);

        try {
            // Log before sending
            Log::info('Attempting to send contact email to infinitrixexpress@gmail.com');

            // Send email
            Mail::to('infinitrixexpress@gmail.com')
                ->send(new ContactFormSubmitted($validated));

            Log::info('Contact email sent successfully');

            return redirect()->route('contact.us')
                ->with('success', 'Your message has been sent successfully! We will get back to you soon.');

        } catch (\Exception $e) {
            Log::error('Contact form submission failed: ' . $e->getMessage());
            
            return redirect()->route('contact.us')
                ->with('error', 'Sorry, there was an error sending your message. Please try again later.');
        }
    }
}
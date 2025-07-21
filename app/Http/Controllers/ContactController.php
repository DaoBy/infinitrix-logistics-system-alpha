<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

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

        // Process the form submission (e.g., send an email)
        // Example: Mail::to('support@infinitrix.com')->send(new ContactFormSubmitted($validated));

        return redirect()->route('contact.us')->with('success', 'Your message has been sent successfully!');
    }
}
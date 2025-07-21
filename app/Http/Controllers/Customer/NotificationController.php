<?php

// app/Http/Controllers/Customer/NotificationController.php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return inertia('Customer/Notifications/Index', [
            'notifications' => $request->user()->notifications()->latest()->get(),
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        $this->authorize('view', $notification);
        $notification->update(['read' => true]);

        return back();
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->notifications()->where('read', false)->update(['read' => true]);

        return back();
    }
}

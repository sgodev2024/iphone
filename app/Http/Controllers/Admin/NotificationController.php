<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    // NotificationController.php
    public function markAsRead($id)
    {
        $notification = Order::find($id);
        if ($notification) {
            $notification->notification = 0; // Giả sử 0 là đã đọc
            $notification->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }
}

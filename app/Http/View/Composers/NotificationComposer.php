<?php
namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;

class NotificationComposer
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        // Dependencies are automatically resolved by the service container...
        $this->orderService = $orderService;
    }

    public function compose(View $view)
    {
        $notifications = $this->orderService->getOrderNotification(Auth::user());
        $view->with('notifications', $notifications);
    }
}

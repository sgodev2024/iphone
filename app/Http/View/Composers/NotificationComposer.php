<?php
namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Services\OrderService;

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
        $notifications = $this->orderService->getOrderNotification();
        $view->with('notifications', $notifications);
    }
}

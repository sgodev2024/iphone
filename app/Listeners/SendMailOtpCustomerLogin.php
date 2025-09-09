<?php

namespace App\Listeners;

use App\Events\CustomerLogin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
class SendMailOtpCustomerLogin implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CustomerLogin $event): void
    {
        $user = $event->user;
        $otp = $event->otp;

        Mail::send('emails.otp_email', ['user' => $user, 'otp' => $otp], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Your OTP Code');
        });
    }
}

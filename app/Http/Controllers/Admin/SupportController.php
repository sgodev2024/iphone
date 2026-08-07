<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CustomerEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Contracts\Providers\Auth;

class SupportController extends Controller
{
    //
    public function contact(){
        $title = "Hỗ trợ";
        return view('admin.sp.index', compact('title'));
    }

    public function feedback(Request $request){
        $authUser = session('authUser');
        $details = [
            'name' => $authUser->name,
            'message' => $request->input('message'),
        ];

        Mail::to('khacthuat.it@gmail.com')->send(new CustomerEmail($details));
        return redirect()->back()->with('success', 'Gửi đánh giá thành công !');
    }
}

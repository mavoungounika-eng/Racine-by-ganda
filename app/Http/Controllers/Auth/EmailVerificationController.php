<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function notice()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('terms.accept');
        }
        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('terms.accept');
        }
        $request->fulfill();
        return redirect()->route('terms.accept');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('terms.accept');
        }
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    }
}

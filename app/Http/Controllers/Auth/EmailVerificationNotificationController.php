<?php

namespace App\Http\Controllers\Auth;

use App\{
    Http\Controllers\Controller,
    Providers\RouteServiceProvider,
    Utils\Helper
};
use Illuminate\{
    Http\RedirectResponse,
    Http\Request
};

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        Helper::validateUserDetails($request);

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}

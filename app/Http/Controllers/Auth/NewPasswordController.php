<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\{
    Auth\Events\PasswordReset,
    Http\RedirectResponse,
    Http\Request,
    Support\Facades\Hash,
    Support\Facades\Password,
    Support\Str,
    Validation\Rules,
    Validation\ValidationException,
    View\View
};

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     *
     * @param  Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @param  Request             $request
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedUser = $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = $this->resetPass($validatedUser);

       return $this->resetPassHandler($status, $request);
    }

    /**
     * @param  array  $userData contains the user data, including their new password
     * @return string
     *
     * Attempts to reset the user's password
     */
    private function resetPass(array $userData): string
    {
        return Password::reset(
            $userData,
            function ($user) use ($userData) {
                $user->forceFill([
                    'password'       => Hash::make($userData["password"]),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );
    }

    /**
     * @param  string           $status   refers to whether the password reset was successful or not
     * @param  Request          $userData refers to the user's data alongside their new password
     * @return RedirectResponse
     *
     * Handles response to the password reset success.
     * Upon failure, redirects to previous route. Redirects to authenticated view upon success.
     */
    private function resetPassHandler(string $status, Request $userData): RedirectResponse
    {
        if(!$status == Password::PASSWORD_RESET) {
            return back()->withInput($userData->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status', __($status));
    }
}

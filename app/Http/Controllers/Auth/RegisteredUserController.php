<?php

namespace App\Http\Controllers\Auth;

use App\{
    Http\Controllers\Controller,
    Models\User,
    Providers\RouteServiceProvider
};
use Illuminate\{
    Auth\Events\Registered,
    Http\RedirectResponse,
    Http\Request,
    Support\Facades\Auth,
    Support\Facades\Hash,
    Validation\Rules,
    Validation\ValidationException,
    View\View
};

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  Request          $request
     * @return RedirectResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedInfo = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()]
        ]);

        $user = User::create([
            'name'     => $validatedInfo["name"],
            'email'    => $validatedInfo["email"],
            'password' => Hash::make($validatedInfo["password"]),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}

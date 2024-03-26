<?php

namespace App\Utils;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class Helper
{
    /**
     * @param  Request|LoginRequest $request refers to the login credentials being validated
     * @return void
     * @throws ValidationException
     *
     * Attempts to validate the login details within the request. Upon failure, throws exception and redirects user
     */
    public static function validateUserDetails(Request|LoginRequest $request): void
    {
        $request->validate([
            "email"    => "required|email",
            "password" => "required|string"
        ]);
    }
}

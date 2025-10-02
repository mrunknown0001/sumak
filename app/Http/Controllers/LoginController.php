<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // check if the user is already authenticated
        if (auth()->check()) {
            return redirect()->route('dashboard'); // Redirect to dashboard if authenticated
        }
        return view('auth.login');
    }
}

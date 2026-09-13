<?php
namespace App\Controllers;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: ' . View::url('dashboard'));
            return;
        }
        View::render('login', [
            'error' => Session::getFlash('login_error'),
        ], null);
    }

    public function doLogin(): void
    {
        $email = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (Auth::tooManyAttempts(strtolower(trim($email)))) {
            Session::flash('login_error', 'För många försök. Vänta 15 minuter och försök igen.');
            header('Location: ' . View::url('login'));
            return;
        }

        if (Auth::attempt($email, $password)) {
            header('Location: ' . View::url('dashboard'));
            return;
        }

        // Uniform message — reveals nothing about which part failed.
        Session::flash('login_error', 'Fel e-post eller lösenord.');
        header('Location: ' . View::url('login'));
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . View::url('login'));
    }
}

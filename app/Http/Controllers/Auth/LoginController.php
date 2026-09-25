<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/user/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login', [
            'loginContext' => 'user',
            'loginHeading' => 'Welcome back,',
            'loginSubheading' => 'Sign in to your Account',
            'loginButtonText' => trans('global.login'),
        ]);
    }

    public function showAdminLoginForm()
    {
        return view('auth.login', [
            'loginContext' => 'admin',
            'loginHeading' => 'Admin Portal',
            'loginSubheading' => 'Sign in to manage the magazine',
            'loginButtonText' => 'Admin Login',
        ]);
    }

    protected function redirectTo()
    {
        $user = auth()->user();

        if ($this->isAdminUser($user)) {
            return route('admin.dashboard');
        }

        return route('user.dashboard');
    }

    protected function authenticated(Request $request, $user)
    {
        $isAdminUser = $this->isAdminUser($user);
        $loginContext = $request->input('login_context');

        if ($loginContext === 'admin' && ! $isAdminUser) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                $this->username() => 'This login is only for administrators.',
            ])->redirectTo(route('admin.login'));
        }

        if ($loginContext === 'user' && $isAdminUser) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                $this->username() => 'Please use the admin login screen.',
            ])->redirectTo(route('login'));
        }
    }

    private function isAdminUser($user): bool
    {
        $roles = $user->roles()->pluck('title')->toArray();

        return $user->is_admin || count(array_intersect($roles, ['SuperAdmin', 'Dairy Admin'])) > 0;
    }
}

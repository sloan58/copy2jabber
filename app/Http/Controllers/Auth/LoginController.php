<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        $credentials = $request->only('email', 'password');
        $message = null;

        if ($user = User::whereEmail($credentials['email'])->first()) {
            $ldapconn = ldap_connect(env('LDAP_HOST'));
            ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 3);
            try {
                ldap_bind($ldapconn, $credentials['email'], $credentials['password']);
                Auth::login($user);
                return $this->sendLoginResponse($request);
            } catch (Exception $e) {
                $message = substr($e->getMessage(), strrpos($e->getMessage(), ':') + 1);
            }
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request, $message);
    }

    protected function redirectTo()
    {
        session()->flash('success', 'You are logged in!');
        return $this->redirectTo;
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

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

    /**
     * La funzione 'authenticated' viene chiamata dopo un login riuscito.
     * Possiamo usarla per controllare se l'utente è associato a una risorsa attiva.
     */
    protected function authenticated(Request $request, $user)
    {
        if ($user->resource && !$user->resource->is_active) {
            // Se la risorsa è inattiva, mostriamo un avviso
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->with('error', 'Il tuo account è associato a una risorsa inattiva. Contatta l\'amministratore.');
        }
        
        // Aggiungiamo un messaggio di benvenuto
        return redirect()->intended($this->redirectPath())
            ->with('success', 'Benvenuto, ' . $user->name . '!');
    }
}

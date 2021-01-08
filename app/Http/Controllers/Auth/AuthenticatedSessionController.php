<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Redirect the user to the OAuth provider
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToProvider(): \Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * read the incoming request and retrieve the user's information from the provider
     * after they are authenticated.
     */
    public function handleProviderCallback()
    {
        $gitHubUser = Socialite::driver('github')->user();
//        dd($gitHubUser);

        $user = User::where('provider_id', $gitHubUser->getId())->first();

        if (!$user) {
            // add user to database
            $user = User::create([
                'email' => $gitHubUser->getEmail(),
                'name' => $gitHubUser->getName() ?? $gitHubUser->getNickname(),
                'provider_id' => $gitHubUser->getId(),
                'provider' => 'github',
            ]);
        }

        // login the user
        Auth::login($user, true);

        return redirect(route('dashboard'));
    }
}

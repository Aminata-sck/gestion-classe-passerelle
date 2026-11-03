<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccessScope;
use App\Services\AuditLogger;
use App\Services\LoginThrottle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private readonly LoginThrottle $throttle, private readonly AuditLogger $audit)
    {
    }

    public function create(): Response
    {
        return Inertia::render('Auth/Connexion');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
        ], [], ['email' => 'e-mail', 'password' => 'mot de passe']);

        $email = mb_strtolower(trim($data['email']));
        $ip = (string) $request->ip();

        if ($this->throttle->estBloque($email, $ip)) {
            $this->audit->log('CONNEXION_BLOQUEE', 'users', null, null, ['email' => $email], $email);

            return back()->withErrors(['email' => 'Trop de tentatives. Réessayez dans 15 minutes.'])->onlyInput('email');
        }

        $user = User::where('email', $email)->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            $this->throttle->echec($email, $ip);
            $this->audit->log('CONNEXION_ECHEC', 'users', $user?->id, null, ['email' => $email], $email);

            return back()->withErrors(['email' => 'E-mail ou mot de passe incorrect.'])->onlyInput('email');
        }

        if (! $user->status) {
            return back()->withErrors(['email' => 'Votre compte est bloqué. Contactez l’administrateur.'])->onlyInput('email');
        }

        if (! AccessScope::pour($user)->estComplet()) {
            return back()->withErrors(['email' => 'Votre rattachement (IA, IEF ou structure) est incomplet. Contactez l’administrateur.'])->onlyInput('email');
        }

        $this->throttle->succes($email, $ip);
        Auth::login($user);
        $request->session()->regenerate();
        $this->audit->log('CONNEXION', 'users', $user->id);

        return $user->must_change_password
            ? redirect()->route('password.edit')
            : redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->audit->log('DECONNEXION', 'users', $request->user()?->id);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function editPassword(Request $request): Response
    {
        return Inertia::render('Auth/MotDePasse', ['obligatoire' => (bool) $request->user()->must_change_password]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)->letters()->numbers()],
        ], [], ['current_password' => 'mot de passe actuel', 'password' => 'nouveau mot de passe']);

        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        $request->session()->regenerate();
        $this->audit->log('MOT_DE_PASSE_CHANGE', 'users', $user->id);

        return redirect()->route('dashboard')->with('success', 'Votre mot de passe a été changé.');
    }
}

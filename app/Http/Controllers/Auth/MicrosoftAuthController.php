<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class MicrosoftAuthController extends Controller
{
    /** Default role for auto-created users from Microsoft SSO. */
    protected const DEFAULT_ROLE = 'manager';

    /**
     * Allowed email domains (from MS_ALLOWED_DOMAINS env, comma-separated).
     * Empty array = all domains allowed (insecure for public servers — set this for production).
     */
    protected function allowedDomains(): array
    {
        $env = (string) env('MS_ALLOWED_DOMAINS', '');
        if ($env === '') return [];
        return array_values(array_filter(array_map('trim', explode(',', $env))));
    }

    public function redirect(): Response
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $msUser = Socialite::driver('azure')->user();
        } catch (\Throwable $e) {
            Log::warning('Microsoft SSO callback failed', ['ex' => $e->getMessage()]);
            return redirect()->route('login')->withErrors([
                'email' => 'Přihlášení přes Microsoft selhalo: ' . $e->getMessage(),
            ]);
        }

        $email = strtolower($msUser->getEmail() ?? '');
        if (!$email) {
            return redirect()->route('login')->withErrors([
                'email' => 'Microsoft účet neposkytl email.',
            ]);
        }

        $domain = substr(strrchr($email, '@'), 1);
        $allowed = $this->allowedDomains();
        if (!empty($allowed) && !in_array($domain, $allowed, true)) {
            return redirect()->route('login')->withErrors([
                'email' => "Účet z domény {$domain} není povolen. Povolené: " . implode(', ', $allowed),
            ]);
        }

        $oid = $msUser->getId();

        // Try to find user by Azure object ID first, then by email
        $user = User::where('microsoft_oid', $oid)->first()
            ?? User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name'          => $msUser->getName() ?: $email,
                'email'         => $email,
                'role'          => self::DEFAULT_ROLE,
                'microsoft_oid' => $oid,
                'password'      => null,
            ]);
            Log::info('Microsoft SSO auto-created user', ['email' => $email, 'role' => self::DEFAULT_ROLE]);
        } elseif (!$user->microsoft_oid) {
            // Existing local user — link Microsoft account
            $user->forceFill(['microsoft_oid' => $oid])->save();
            Log::info('Microsoft SSO linked to existing user', ['email' => $email]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }
}

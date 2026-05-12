<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if user exists by google_id
            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                // User exists, create token
                $token = $user->createToken('auth_token')->plainTextToken;
                return $this->redirectWithToken($token, $user);
            }

            // Check if email exists
            $existingUser = User::where('email', $googleUser->email)->first();

            if ($existingUser) {
                // Link Google account to existing user
                $existingUser->update(['google_id' => $googleUser->id]);
                $token = $existingUser->createToken('auth_token')->plainTextToken;
                return $this->redirectWithToken($token, $existingUser);
            }

            // Create new user
            $newUser = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'email_verified_at' => now(),
                'role' => 'user',
            ]);

            // Auto-create wallet for new Google users
            Wallet::create(['user_id' => $newUser->id, 'balance' => 0]);

            $token = $newUser->createToken('auth_token')->plainTextToken;
            return $this->redirectWithToken($token, $newUser);
        } catch (\Exception $e) {
            Log::error('Google authentication error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect(env('FRONTEND_URL') . '/login?error=authentication_failed');
        }
    }

    private function redirectWithToken($token, $user)
    {
        $frontendUrl = env('FRONTEND_URL');
        $isFrozen = $user->isFrozen() ? 'true' : 'false';

        return redirect($frontendUrl . '/google-auth-success?token=' . $token . '&user=' . urlencode(json_encode($user)) . '&is_frozen=' . $isFrozen);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}


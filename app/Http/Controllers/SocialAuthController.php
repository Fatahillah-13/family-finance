<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        // Cari user berdasarkan email
        $user = User::query()->where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Google User'),
                'email' => $googleUser->getEmail(),
                'email_verified_at' => now(),
                // password dummy agar kolom tidak null (kalau kolom password required)
                'password' => bcrypt(Str::random(32)),
            ]);
        } else {
            // Optional: kalau user lama belum verified, verified-kan
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }
        }

        // Login user
        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}

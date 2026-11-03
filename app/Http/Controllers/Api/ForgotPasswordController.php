<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtp;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function sendOtp(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = Str::lower($data['email']);
        $key = 'password-reset-send:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return response()->json(['message' => 'Please wait before requesting another code.', 'retry_after' => RateLimiter::availableIn($key)], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            DB::transaction(function () use ($email) {
                // Lock the account too, so simultaneous first requests cannot create two codes.
                $user = User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
                if (! $user) {
                    return;
                }
                $otp = (string) random_int(100000, 999999);
                DB::table('password_reset_otps')->updateOrInsert(['email' => $email], [
                    'user_id' => $user->id,
                    'otp_hash' => Hash::make($otp),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(10),
                    'reset_token_hash' => null,
                    'reset_expires_at' => null,
                ]);
                Mail::to($user->email)->send(new PasswordResetOtp($otp));
            });
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to send a reset code right now. Please try again later.'], 503);
        }

        // Use the same response for unknown emails to avoid disclosing account membership.
        return response()->json(['message' => 'If an account exists for this email, a password reset code has been sent.', 'expires_in' => 600]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ]);
        $email = Str::lower($data['email']);
        $token = DB::transaction(function () use ($email, $data) {
            $row = DB::table('password_reset_otps')->where('email', $email)->lockForUpdate()->first();
            if (! $row || ! $row->otp_hash || $row->attempts >= 5 || now()->gte($row->expires_at)) {
                return null;
            }
            if (! Hash::check($data['otp'], $row->otp_hash)) {
                DB::table('password_reset_otps')->where('email', $email)->increment('attempts');

                return null;
            }
            $token = Str::random(64);
            DB::table('password_reset_otps')->where('email', $email)->update([
                'otp_hash' => null,
                'reset_token_hash' => hash('sha256', $token),
                'reset_expires_at' => now()->addMinutes(10),
            ]);

            return $token;
        });
        if (! $token) {
            throw ValidationException::withMessages(['otp' => ['The code is invalid, expired or has too many failed attempts. Request a new code.']]);
        }

        return response()->json(['message' => 'Code verified. You can now set a new password.', 'reset_token' => $token, 'expires_in' => 600])
            ->header('Cache-Control', 'no-store');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reset_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);
        $email = Str::lower($data['email']);
        $reset = DB::transaction(function () use ($email, $data) {
            $row = DB::table('password_reset_otps')->where('email', $email)->lockForUpdate()->first();
            if (! $row || ! $row->reset_token_hash || ! $row->reset_expires_at || now()->gte($row->reset_expires_at)
                || ! hash_equals($row->reset_token_hash, hash('sha256', $data['reset_token']))) {
                return false;
            }
            $user = User::where('id', $row->user_id)->whereRaw('LOWER(email) = ?', [$email])->first();
            if (! $user) {
                return false;
            }
            $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
            ApiToken::where('user_id', $user->id)->delete();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            DB::table('password_reset_otps')->where('email', $email)->delete();

            return true;
        });
        if (! $reset) {
            throw ValidationException::withMessages(['reset_token' => ['The reset token is invalid or expired. Request a new code.']]);
        }

        return response()->json(['message' => 'Password reset successfully. Please log in with your new password.']);
    }
}

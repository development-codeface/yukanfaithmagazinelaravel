<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
  public function register(Request $request)
{
    try {

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Get the Free Trial plan
        $freePlan = Plan::where('name', 'Free Trial')->first();
        
        // Create free subscription for 1 month if Free Trial plan exists
        if ($freePlan) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $freePlan->id,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonth(),
                'status' => 'active',
            ]);
        }

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'token' => $this->createToken($user),
        ], 201);

    } catch (ValidationException $e) {

        return response()->json([
            'message' => 'Validation failed.',
            'errors' => $e->errors(),
        ], 422);
    }
}

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
             return response()->json([
            'message' => 'The provided credentials are incorrect.',
            'errors' => [
                'email' => ['The provided credentials are incorrect.']
            ]
        ], 422);
        }

        return response()->json([
            'user' => $user,
            'token' => $this->createToken($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    private function createToken(User $user): string
    {
        $plainToken = Str::random(60);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'api',
            'token' => Hash::make($plainToken),
        ]);

        return $plainToken;
    }
}

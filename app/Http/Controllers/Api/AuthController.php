<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
  public function register(Request $request)
{
    try {

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'magazine_id' => ['nullable', 'integer', 'exists:magazine_issues,id'],
            'delivery_type' => ['nullable', 'in:digital,print,both'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        \App\Support\RegistrationTrial::grant($user);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'token' => $this->createToken($user),
            'purchase_context' => $this->purchaseContext($user, $data['magazine_id'] ?? null, $data['delivery_type'] ?? 'digital'),
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
            'magazine_id' => ['nullable', 'integer', 'exists:magazine_issues,id'],
            'delivery_type' => ['nullable', 'in:digital,print,both'],
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
            'purchase_context' => $this->purchaseContext($user, $credentials['magazine_id'] ?? null, $credentials['delivery_type'] ?? 'digital'),
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

    private function purchaseContext(User $user, ?int $magazineId, string $deliveryType = 'digital'): array
    {
        $magazine = $magazineId ? \App\Models\MagazineIssue::find($magazineId) : null;
        $canRead = $magazine ? $user->canReadMagazine($magazine) : null;
        return [
            'magazine_id' => $magazine?->id,
            'can_read' => $canRead,
            'delivery_type' => $magazine ? $deliveryType : null,
            'show_selected_magazine' => $magazine !== null,
            'magazine' => $magazine ? new \App\Http\Resources\MagazineIssueResource($magazine) : null,
            'next_action' => $magazine && (!$canRead || $deliveryType !== 'digital') ? 'subscriptions' : 'dashboard',
        ];
    }
}

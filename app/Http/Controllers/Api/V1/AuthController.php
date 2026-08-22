<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

#[Group('Auth')]
class AuthController extends Controller
{
    /**
     * Creates a user account and returns the new user resource.
     */
    #[Response(status: 201, description: 'User registered successfully', examples: [[
        'message' => 'user registered successfully',
        'user' => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-01-01T00:00:00.000000Z'],
    ]])]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        return response()->json([
            'message' => 'user registered successfully',
            'user' => $user->toResource(),
        ], 201);
    }

    /**
     * Verifies credentials and returns a bearer token valid for 24 hours.
     */
    #[Response(status: 200, description: 'Logged in successfully', examples: [[
        'message' => 'logged in successfully',
        'user' => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-01-01T00:00:00.000000Z'],
        'token' => '1|plainTextTokenExample123',
    ]])]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'invalid credentials',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'logged in successfully',
            'user' => $user->toResource(),
            'token' => $token,
        ]);
    }

    /**
     * User logout.
     */
    #[Response(status: 200, description: 'Logged out successfully', examples: [[
        'message' => 'logged out successfully',
    ]])]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'logged out successfully',
        ]);
    }
}

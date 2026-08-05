<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * User register.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password)
        ]);

        return response()->json([
            "message" => "user registered successfully",
            "user" => $user->toResource(),
        ],201);
    }

    /**
     * User login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where("email",$request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                "message" => "invalid credentials"
            ],401);
        }

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => "logged in successfully",
            "user" => $user->toResource(),
            "token" => "Bearer " . $token
        ]);
    }

    /**
     *  User logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user("sanctum")->currentAccessToken()->delete();
        return response()->json([
            "message" => "logged out successfully"
        ]);
    }
}

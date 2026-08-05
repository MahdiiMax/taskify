<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test("user can register with valid credentials", function () {
    $response = $this->postJson(route("api.v1.auth.register"), [
        "name" => "test user 1",
        "email" => "test@emil.com",
        "password" => "password123",
        "password_confirmation" => "password123"
    ]);

    $response->assertStatus(201)->assertJsonStructure([
        "message",
        "user" => ["id", "name", "email"]
    ]);

    $this->assertDatabaseHas("users", [
        "email" => "test@emil.com"
    ]);
});

test("user can not register with duplicated email", function () {
    User::factory()->create(["email" => "duplicated@test.com"]);
    $response = $this->postJson(route("api.v1.auth.register"), [
        "name" => "test user 2",
        "email" => "duplicated@test.com",
        "password" => "password123",
        "password_confirmation" => "password123"
    ]);
    $response->assertStatus(422)->assertJsonValidationErrorFor("email");
});

test("user can login with correct credentials and receive token", function () {
    $user = User::factory()->create([
        "email" => "user@example.com",
        "password" => bcrypt("password123"),
    ]);

    $response = $this->postJson(route("api.v1.auth.login"), [
        "email" => "user@example.com",
        "password" => "password123",
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(["token", "user"]);
});

test("user can not login with incorrect password", function () {
    $user = User::factory()->create([
        "email" => "user@example.com",
        "password" => bcrypt("password123"),
    ]);

    $response = $this->postJson(route("api.v1.auth.login"), [
        "email" => "user@example.com",
        "password" => "wrong password",
    ]);

    $response->assertStatus(401)
        ->assertJsonStructure(["message"]);
});

test("authenticated user can logout and revoke token", function () {
    $user = User::factory()->create();
    $token = $user->createToken("auth_token")->plainTextToken;
    $response = $this->withHeader("Authorization", "Bearer {$token}")
        ->postJson(route("api.v1.auth.logout"));

    $response->assertStatus(200)
        ->assertJson(["message" => "logged out successfully"]);

    expect($user->tokens()->count())->toBe(0);
});

test("authenticated user cannot hit login or register route (Guest Middleware Test)", function () {
    $user = User::factory()->create();
    $token = $user->createToken("auth_token")->plainTextToken;

    $response = $this->withHeader("Authorization", "Bearer {$token}")
        ->postJson(route("api.v1.auth.login"), [
            "email" => $user->email,
            "password" => "password",
        ]);

    $response->assertStatus(400)
        ->assertJson(["message" => "You are already authenticated. Please log out first."]);
});
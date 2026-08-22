<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can create a task', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('api.v1.tasks.store'), [
        'title' => 'Test Task',
        'description' => 'This is a test task.',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    $response->assertStatus(201)->assertJsonPath('data.title', 'Test Task')->assertJsonPath('data.description', 'This is a test task.')->assertJsonPath('data.status', TaskStatus::PENDING->value);
    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task',
        'description' => 'This is a test task.',
        'status' => TaskStatus::PENDING->value,
        'user_id' => $user->id,
    ]);
});

test('unauthenticated user cannot create a task', function () {
    $response = $this->postJson(route('api.v1.tasks.store'), [
        'title' => 'Test Task',
        'description' => 'This is a test task.',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    $response->assertStatus(401);
});

test('authenticated user can view their tasks', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Task::factory(3)->create(['user_id' => $user1->id]);
    Task::factory(2)->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)->getJson(route('api.v1.tasks.index'));

    $response->assertStatus(200)->assertJsonCount(3, 'data');
});

test('user cannot view or update tasks of another user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Task::factory(3)->create(['user_id' => $user1->id]);
    Task::factory(2)->create(['user_id' => $user2->id]);

    $responseShow = $this->actingAs($user2)->getJson(route('api.v1.tasks.show', ['task' => Task::where('user_id', $user1->id)->first()->id]));
    $responseShow->assertStatus(403);

    $responseUpdate = $this->actingAs($user2)->putJson(route('api.v1.tasks.update', ['task' => Task::where('user_id', $user1->id)->first()->id]), [
        'title' => 'Updated Task',
    ]);
    $responseUpdate->assertStatus(403);
});

test('user can filter tasks by status and priority and search queries', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Test Task 1', 'status' => TaskStatus::PENDING->value, 'priority' => TaskPriority::HIGH->value]);
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Test Task 2', 'status' => TaskStatus::DONE->value, 'priority' => TaskPriority::LOW->value]);
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Test Task 3', 'status' => TaskStatus::PENDING->value, 'priority' => TaskPriority::MEDIUM->value]);

    $response = $this->actingAs($user)->getJson(route('api.v1.tasks.index', ['status' => TaskStatus::PENDING->value, 'priority' => TaskPriority::HIGH->value]))->assertStatus(200)->assertJsonCount(1, 'data');

    $searchResponse = $this->actingAs($user)->getJson(route('api.v1.tasks.index', ['search' => 'Test Task 2']))->assertStatus(200)->assertJsonCount(1, 'data');
});

test('user can not give wrong status & priority query', function () {
    $user = User::factory()->create();
    Task::factory(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('api.v1.tasks.index', ['status' => 'wrong', 'priority' => 'wrong']));

    $response->assertStatus(422);
});

test('user can search tasks case-insensitively', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Buy MILK', 'description' => 'from the store']);

    $this->actingAs($user)
        ->getJson(route('api.v1.tasks.index', ['search' => 'buy milk']))
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('user can soft delete and restore a task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route('api.v1.tasks.destroy', ['task' => $task->id]));
    $response->assertNoContent();
    $this->assertSoftDeleted('tasks', ['id' => $task->id]);

    $restoreResponse = $this->actingAs($user)->postJson(route('api.v1.tasks.restore', ['task' => $task->id]));
    $restoreResponse->assertStatus(200);
});

test("user cannot delete another user's task", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2)
        ->deleteJson(route('api.v1.tasks.destroy', $task))
        ->assertStatus(403);

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
});

test("user cannot restore another user's task", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user1->id]);
    $task->delete();

    $this->actingAs($user2)
        ->postJson(route('api.v1.tasks.restore', $task))
        ->assertStatus(403);

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('authenticated user can view their trashed tasks', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $trashed = Task::factory(3)->create(['user_id' => $user1->id]);
    $trashed->each->delete();

    Task::factory(2)->create(['user_id' => $user1->id]);
    Task::factory()->create(['user_id' => $user2->id])->delete();

    $this->actingAs($user1)
        ->getJson(route('api.v1.tasks.trashed'))
        ->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('token-authenticated user can list tasks', function () {
    $user = User::factory()->create();
    Task::factory(2)->create(['user_id' => $user->id]);
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('api.v1.tasks.index'))
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('user with expired token cannot access protected routes', function () {
    $user = User::factory()->create();
    $expired = $user->createToken('expired', ['*'], now()->subMinute())->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$expired}")
        ->getJson(route('api.v1.tasks.index'))
        ->assertStatus(401);
});

test('token-authenticated user can create a task', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(route('api.v1.tasks.store'), [
            'title' => 'Token Task',
            'description' => 'Created via bearer token.',
            'status' => TaskStatus::PENDING->value,
            'priority' => TaskPriority::MEDIUM->value,
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Token Task',
        'user_id' => $user->id,
    ]);
});

test('token-authenticated user can update a task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson(route('api.v1.tasks.update', $task), [
            'title' => 'Updated by token',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.title', 'Updated by token');
});

test('token-authenticated user can delete a task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(route('api.v1.tasks.destroy', $task))
        ->assertNoContent();

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test("token-authenticated user cannot access another user's task", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user1->id]);
    $token = $user2->createToken('auth_token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('api.v1.tasks.show', $task))
        ->assertStatus(403);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson(route('api.v1.tasks.update', $task), ['title' => 'Nope'])
        ->assertStatus(403);
});

test('request with invalid token is rejected', function () {
    $this->withHeader('Authorization', 'Bearer invalid-token')
        ->getJson(route('api.v1.tasks.index'))
        ->assertStatus(401);
});

test('tasks list supports custom per_page', function () {
    $user = User::factory()->create();
    Task::factory(15)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->getJson(route('api.v1.tasks.index', ['per_page' => 5]))
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5);
});

test('per_page above 100 is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.tasks.index', ['per_page' => 101]))
        ->assertStatus(422);
});

test('task model casts status and priority to enums', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    expect($task->status)->toBeInstanceOf(TaskStatus::class)
        ->and($task->priority)->toBeInstanceOf(TaskPriority::class);
});

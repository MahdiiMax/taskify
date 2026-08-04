<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test("authenticated user can create a task", function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route("api.v1.tasks.store"), [
        "title" => "Test Task",
        "description" => "This is a test task.",
        "status" => TaskStatus::PENDING->value,
        "priority" => TaskPriority::MEDIUM->value,
    ]);

    $response->assertStatus(201)->assertJsonPath("data.title", "Test Task")->assertJsonPath("data.description", "This is a test task.")->assertJsonPath("data.status", TaskStatus::PENDING->value);
    $this->assertDatabaseHas("tasks", [
        "title" => "Test Task",
        "description" => "This is a test task.",
        "status" => TaskStatus::PENDING->value,
        "user_id" => $user->id,
    ]);
});

test("unauthenticated user cannot create a task", function () {
    $response = $this->postJson(route("api.v1.tasks.store"), [
        "title" => "Test Task",
        "description" => "This is a test task.",
        "status" => TaskStatus::PENDING->value,
        "priority" => TaskPriority::MEDIUM->value,
    ]);

    $response->assertStatus(401);
});

test("authenticated user can view their tasks", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Task::factory(3)->create(["user_id" => $user1->id]);
    Task::factory(2)->create(["user_id" => $user2->id]);

    $response = $this->actingAs($user1)->getJson(route("api.v1.tasks.index"));

    $response->assertStatus(200)->assertJsonCount(3, "data");
});

test("user cannot view or update tasks of another user", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Task::factory(3)->create(["user_id" => $user1->id]);
    Task::factory(2)->create(["user_id" => $user2->id]);

    $responseShow = $this->actingAs($user2)->getJson(route("api.v1.tasks.show", ["task" => Task::where("user_id", $user1->id)->first()->id]));
    $responseShow->assertStatus(403);

    $responseUpdate = $this->actingAs($user2)->putJson(route("api.v1.tasks.update", ["task" => Task::where("user_id", $user1->id)->first()->id]), [
        "title" => "Updated Task"
    ]);
    $responseUpdate->assertStatus(403);
});

test("user can filter tasks by status and priority and search queries", function () {
    $user = User::factory()->create();
    Task::factory()->create(["user_id" => $user->id, "title" => "Test Task 1", "status" => TaskStatus::PENDING->value, "priority" => TaskPriority::HIGH->value]);
    Task::factory()->create(["user_id" => $user->id, "title" => "Test Task 2", "status" => TaskStatus::DONE->value, "priority" => TaskPriority::LOW->value]);
    Task::factory()->create(["user_id" => $user->id, "title" => "Test Task 3", "status" => TaskStatus::PENDING->value, "priority" => TaskPriority::MEDIUM->value]);

    $response = $this->actingAs($user)->getJson(route("api.v1.tasks.index", ["status" => TaskStatus::PENDING->value, "priority" => TaskPriority::HIGH->value]))->assertStatus(200)->assertJsonCount(1, "data");

    $searchResponse = $this->actingAs($user)->getJson(route("api.v1.tasks.index", ["search" => "Test Task 2"]))->assertStatus(200)->assertJsonCount(1, "data");
});

test("user can soft delete and restore a task", function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(["user_id" => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route("api.v1.tasks.destroy", ["task" => $task->id]));
    $response->assertStatus(200);
    $this->assertSoftDeleted("tasks", ["id" => $task->id]);

    $restoreResponse = $this->actingAs($user)->postJson(route("api.v1.tasks.restore", ["task" => $task->id]));
    $restoreResponse->assertStatus(200);
});

test("user cannot delete another user's task", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $task = Task::factory()->create(["user_id" => $user1->id]);

    $this->actingAs($user2)
        ->deleteJson(route("api.v1.tasks.destroy", $task))
        ->assertStatus(403);

    $this->assertDatabaseHas("tasks", ["id" => $task->id, "deleted_at" => null]);
});

test("user cannot restore another user's task", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $task = Task::factory()->create(["user_id" => $user1->id]);
    $task->delete();

    $this->actingAs($user2)
        ->postJson(route("api.v1.tasks.restore", $task))
        ->assertStatus(403);

    $this->assertSoftDeleted("tasks", ["id" => $task->id]);
});

test("authenticated user can view their trashed tasks", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $trashed = Task::factory(3)->create(["user_id" => $user1->id]);
    $trashed->each->delete();

    Task::factory(2)->create(["user_id" => $user1->id]);
    Task::factory()->create(["user_id" => $user2->id])->delete();

    $this->actingAs($user1)
        ->getJson(route("api.v1.tasks.trashed"))
        ->assertStatus(200)
        ->assertJsonCount(3, "data");
});

<?php

use App\Enums\ProjectColor;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can create a project', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson(route('api.v1.projects.store'), [
        'name' => 'Development',
        'description' => 'Backend development tasks',
        'color' => ProjectColor::BLUE->value,
    ]);
    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Development')
        ->assertJsonPath('data.color', ProjectColor::BLUE->value);
    $this->assertDatabaseHas('projects', [
        'name' => 'Development',
        'user_id' => $user->id,
    ]);
});

test('unauthenticated user cannot create a project', function () {
    $response = $this->postJson(route('api.v1.projects.store'), [
        'name' => 'Development',
    ]);
    $response->assertStatus(401);
});

test('authenticated user can view their projects', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Project::factory(3)->create(['user_id' => $user1->id]);
    Project::factory(2)->create(['user_id' => $user2->id]);
    $response = $this->actingAs($user1)->getJson(route('api.v1.projects.index'));
    $response->assertStatus(200)->assertJsonCount(3, 'data');
});

test('user cannot view or update projects of another user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $project1 = Project::factory()->create(['user_id' => $user1->id]);
    $this->actingAs($user2)
        ->getJson(route('api.v1.projects.show', $project1))
        ->assertStatus(403);
    $this->actingAs($user2)
        ->putJson(route('api.v1.projects.update', $project1), [
            'name' => 'Hacked Project',
        ])
        ->assertStatus(403);
});

test('user can update their project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);
    $response = $this->actingAs($user)->putJson(route('api.v1.projects.update', $project), [
        'name' => 'New Name',
    ]);
    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'New Name');
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'New Name',
    ]);
});

test('user can soft delete and restore a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user)
        ->deleteJson(route('api.v1.projects.destroy', $project))
        ->assertNoContent();
    $this->assertSoftDeleted('projects', ['id' => $project->id]);
    $this->actingAs($user)
        ->postJson(route('api.v1.projects.restore', $project))
        ->assertStatus(200);
    $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
});

test("user cannot restore another user's project", function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user1->id]);
    $project->delete();

    $this->actingAs($user2)
        ->postJson(route('api.v1.projects.restore', $project))
        ->assertStatus(403);

    $this->assertSoftDeleted('projects', ['id' => $project->id]);
});

test('user can view their trashed projects', function () {
    $user = User::factory()->create();
    $trashedProjects = Project::factory(2)->create(['user_id' => $user->id]);
    $trashedProjects->each->delete();
    Project::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user)
        ->getJson(route('api.v1.projects.trashed'))
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('user can filter tasks by project id', function () {
    $user = User::factory()->create();
    $project1 = Project::factory()->create(['user_id' => $user->id]);
    $project2 = Project::factory()->create(['user_id' => $user->id]);
    Task::factory()->create(['user_id' => $user->id, 'project_id' => $project1->id, 'title' => 'Project 1 Task']);
    Task::factory()->create(['user_id' => $user->id, 'project_id' => $project2->id, 'title' => 'Project 2 Task']);
    $response = $this->actingAs($user)
        ->getJson(route('api.v1.tasks.index', ['project_id' => $project1->id]));
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Project 1 Task');
});

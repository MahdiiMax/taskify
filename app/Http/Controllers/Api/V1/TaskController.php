<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Task\IndexTaskRequest;
use App\Http\Requests\Api\V1\Task\StoreTaskRequest;
use App\Http\Requests\Api\V1\Task\UpdateTaskRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Task;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Tasks')]
class TaskController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the task.
     */
    #[Response(status: 200, description: 'Paginated list of the user\'s tasks', examples: [[
        'data' => [['id' => 1, 'title' => 'Buy milk', 'description' => 'From the store', 'status' => 'pending', 'priority' => 'medium', 'due_date' => '2026-08-07', 'created_at' => '2026-08-06T00:00:00.000000Z']],
        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'path' => 'http://localhost:8000/api/v1/tasks', 'per_page' => 10, 'to' => 1, 'total' => 1],
    ]])]
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $tasks = $request->user()->tasks()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.strtolower($request->input('search')).'%';
                $query->where(function ($query) use ($search) {
                    $query->whereRaw('LOWER(title) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$search]);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('priority'), function ($query) use ($request) {
                $query->where('priority', $request->input('priority'));
            })->when($request->filled('project_id'), function ($query) use ($request) {
                $query->where('project_id', $request->input('project_id'));
            })->latest()->paginate(10);

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created task in storage.
     */
    #[Response(status: 201, description: 'Task created successfully', examples: [[
        'message' => 'Task created successfully',
        'data' => ['id' => 1, 'title' => 'Buy milk', 'description' => 'From the store', 'status' => 'pending', 'priority' => 'medium', 'due_date' => '2026-08-07', 'created_at' => '2026-08-06T00:00:00.000000Z'],
    ]])]
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->validated());

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task->toResource(),
        ], 201);
    }

    /**
     * Display the specified task.
     */
    #[Response(status: 200, description: 'Task details', examples: [[
        'data' => ['id' => 1, 'title' => 'Buy milk', 'description' => 'From the store', 'status' => 'pending', 'priority' => 'medium', 'due_date' => '2026-08-07', 'user' => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-01-01T00:00:00.000000Z'], 'created_at' => '2026-08-06T00:00:00.000000Z'],
    ]])]
    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    /**
     * Update the specified task in storage.
     */
    #[Response(status: 200, description: 'Task updated successfully', examples: [[
        'message' => 'Task updated successfully',
        'data' => ['id' => 1, 'title' => 'Buy milk', 'description' => 'From the store', 'status' => 'done', 'priority' => 'medium', 'due_date' => '2026-08-07', 'user' => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-01-01T00:00:00.000000Z'], 'created_at' => '2026-08-06T00:00:00.000000Z'],
    ]])]
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        $task->update($request->validated());

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => $task->toResource(),
        ]);
    }

    /**
     * Remove the specified task from storage.
     */
    #[Response(status: 204, description: 'Task deleted successfully', examples: [[
        'message' => 'Task deleted successfully',
    ]])]
    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ], 204);
    }

    /**
     * Display a listing of trashed tasks.
     */
    #[Response(status: 200, description: 'Paginated list of the user\'s trashed tasks', examples: [[
        'data' => [['id' => 1, 'title' => 'Buy milk', 'description' => 'From the store', 'status' => 'pending', 'priority' => 'medium', 'due_date' => '2026-08-07', 'user' => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-01-01T00:00:00.000000Z'], 'created_at' => '2026-08-06T00:00:00.000000Z']],
        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'path' => 'http://localhost:8000/api/v1/tasks/trashed', 'per_page' => 10, 'to' => 1, 'total' => 1],
    ]])]
    public function trashed(Request $request): AnonymousResourceCollection
    {
        $trashedTasks = $request->user()->tasks()->onlyTrashed()->latest()->paginate(10);

        return TaskResource::collection($trashedTasks);
    }

    /**
     * Restore the specified task.
     */
    #[Response(status: 200, description: 'Task restored successfully', examples: [[
        'data' => ['id' => 1, 'title' => 'Buy milk', 'description' => 'From the store', 'status' => 'pending', 'priority' => 'medium', 'due_date' => '2026-08-07', 'user' => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-01-01T00:00:00.000000Z'], 'created_at' => '2026-08-06T00:00:00.000000Z'],
    ]])]
    public function restore(Task $task): TaskResource
    {
        $this->authorize('restore', $task);
        $task->restore();

        return new TaskResource($task);
    }
}

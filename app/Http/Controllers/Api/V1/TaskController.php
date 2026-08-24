<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
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
use Illuminate\Http\Response as HttpResponse;

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
            ->filter($request->validated())
            ->sort($request->input('sort'))
            ->paginate((int) $request->validated('per_page', 10));

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
        $task->loadMissing('user');

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
        $task->loadMissing('user');

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => $task->toResource(),
        ]);
    }

    /**
     * Remove the specified task from storage.
     */
    #[Response(status: 204, description: 'Task deleted successfully')]
    public function destroy(Task $task): HttpResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->noContent();
    }

    /**
     * Display a listing of trashed tasks.
     */
    #[Response(status: 200, description: 'Paginated list of the user\'s trashed tasks', examples: [[
        'data' => [['id' => 1, 'title' => 'Buy milk', 'description' => 'From the store', 'status' => 'pending', 'priority' => 'medium', 'due_date' => '2026-08-07', 'user' => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-01-01T00:00:00.000000Z'], 'created_at' => '2026-08-06T00:00:00.000000Z']],
        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'path' => 'http://localhost:8000/api/v1/tasks/trashed', 'per_page' => 10, 'to' => 1, 'total' => 1],
    ]])]
    public function trashed(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $trashedTasks = $request->user()->tasks()
            ->onlyTrashed()
            ->latest()
            ->paginate((int) $request->validated('per_page', 10));

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

    /**
     * Aggregate statistics for the authenticated user's tasks.
     */
    #[Response(status: 200, description: 'Task statistics', examples: [[
        'data' => [
            'total' => 42,
            'by_status' => ['pending' => 10, 'in_progress' => 12, 'done' => 20],
            'by_priority' => ['low' => 8, 'medium' => 14, 'high' => 20],
            'overdue' => 3,
            'due_today' => 2,
            'completed_this_week' => 5,
        ],
    ]])]
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $byStatus = $user->tasks()
            ->select('status')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $byPriority = $user->tasks()
            ->select('priority')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority');

        return response()->json([
            'data' => [
                'total' => $user->tasks()->count(),
                'by_status' => collect(TaskStatus::cases())
                    ->mapWithKeys(fn (TaskStatus $status) => [$status->value => (int) ($byStatus[$status->value] ?? 0)]),
                'by_priority' => collect(TaskPriority::cases())
                    ->mapWithKeys(fn (TaskPriority $priority) => [$priority->value => (int) ($byPriority[$priority->value] ?? 0)]),
                'overdue' => $user->tasks()
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->where('status', '!=', TaskStatus::DONE->value)
                    ->count(),
                'due_today' => $user->tasks()
                    ->whereBetween('due_date', [now()->startOfDay(), now()->endOfDay()])
                    ->count(),
                'completed_this_week' => $user->tasks()
                    ->where('status', TaskStatus::DONE->value)
                    ->where('updated_at', '>=', now()->startOfWeek())
                    ->count(),
            ],
        ]);
    }
}

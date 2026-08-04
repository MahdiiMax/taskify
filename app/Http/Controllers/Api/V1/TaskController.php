<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Task\StoreTaskRequest;
use App\Http\Requests\Api\V1\Task\UpdateTaskRequest;
use App\Http\Resources\V1\TaskResource;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class TaskController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the task.
     */
    public function index(Request $request)
    {
        $tasks = $request->user()->tasks()
        ->when($request->filled("search"),function ($query) use ($request){
            $query->whereAny(["title","description"],"like","%{$request->input('search')}%");
        })
        ->when($request->filled("status"),function ($query) use ($request){
            $query->where("status","like","%{$request->input('status')}%");
        })
        ->when($request->filled("prioriy"),function ($query) use ($request){
            $query->where("prioriy","like","%{$request->input('prioriy')}%");
        })->latest()->paginate(10);

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->validated());
        return  response()->json([
            "message" => "Task created successfully",
            "data" => $task->toResource()
        ],201);
    }

    /**
     * Display the specified task.
     */
    public function show(Request $request, Task $task)
    {
        $this->authorize("view",$task);
        return new TaskResource($task);
    }

    /**
     * Update the specified task in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize("update",$task);
        $task->update($request->validated());
        return response()->json([
            "message" => "Task updated successfully",
            "date" => $task->toResource()
        ]);
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize("delete",$task);
        $task->delete();
        return response()->json([
            "message" => "Task deleted successfully"
        ]);
    }

    /**
     * Display a listing of trashed tasks.
     */
    public function trashed(Request $request)
    {
        $trashedTasks = $request->user()->tasks()->onlyTrashed()->latest()->paginate(10);
        return TaskResource::collection($trashedTasks);
    }


    /**
     * Restore the specified task.
     */
    public function restore(Request $request, Task $task)
    {
        $this->authorize("restore",$task);
        $task->restore();
        return new TaskResource($task);
    }
}
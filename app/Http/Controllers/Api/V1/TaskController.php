<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Task\StoreTaskRequest;
use App\Http\Requests\Api\V1\Task\UpdateTaskRequest;
use App\Http\Resources\V1\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tasks = $request->user()->tasks();
        if($request->filled("search")){
            $tasks->whereAny(["title","description"],"like","{$request->input('search')}");
        }
        if($request->filled("status")){
            $tasks->where("status",$request->input("status"));
        }
        if($request->filled("prioriy")){
            $tasks->where("prioriy",$request->input("prioriy"));
        }

        $tasks = $tasks->latest()->paginate(10);
        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        if($task->user_id != $request->user()->id){
            return response()->json(["message" => "Unauthorized acces to task"],403);
        }
        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        if($task->user_id != $request->user()->id){
            return response()->json(["message" => "Unauthorized acces to task"],403);
        }
        $task->update($request->validated());
        return response()->json([
            "message" => "Task updated successfully",
            "date" => $task->toResource()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        if($task->user_id != $request->user()->id){
            return response()->json(["message" => "Unauthorized acces to task"],403);
        }
        $task->delete();
        return response()->json([
            "message" => "Task deleted successfully"
        ]);
    }
}

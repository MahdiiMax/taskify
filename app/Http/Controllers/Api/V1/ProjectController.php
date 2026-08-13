<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Project\StoreProjectRequest;
use App\Http\Requests\Api\V1\Project\UpdateProjectRequest;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Projects')]
class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project', ['except' => ['index', 'store']]);
    }

    /**
     * Display a listing of the resource.
     */
    #[Response(status: 200, description: 'Paginated list of the user\'s projects', examples: [[
        'data' => [['id' => 1, 'name' => 'Work', 'description' => 'Work projects', 'color' => 'blue', 'created_at' => '2026-08-06T00:00:00.000000Z']],
        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'path' => 'http://localhost:8000/api/v1/projects', 'per_page' => 10, 'to' => 1, 'total' => 1],
    ]])]
    public function index(): AnonymousResourceCollection
    {
        $projects = request()->user()->projects()->latest()->paginate(10);

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Response(status: 201, description: 'Project created successfully', examples: [[
        'message' => 'Project created successfully',
        'data' => ['id' => 1, 'name' => 'Work', 'description' => 'Work projects', 'color' => 'blue', 'created_at' => '2026-08-06T00:00:00.000000Z'],
    ]])]
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = request()->user()->projects()->create($request->validated());

        return response()->json([
            'message' => 'Project created successfully',
            'data' => $project->toResource(),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    #[Response(status: 200, description: 'Project details', examples: [[
        'data' => ['id' => 1, 'name' => 'Work', 'description' => 'Work projects', 'color' => 'blue', 'created_at' => '2026-08-06T00:00:00.000000Z'],
    ]])]
    public function show(Project $project): ProjectResource
    {
        return new ProjectResource($project);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Response(status: 200, description: 'Project updated successfully', examples: [[
        'message' => 'Project updated successfully',
        'data' => ['id' => 1, 'name' => 'Work Updated', 'description' => 'Work projects', 'color' => 'blue', 'created_at' => '2026-08-06T00:00:00.000000Z'],
    ]])]
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project->update($request->validated());

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => $project->toResource(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Response(status: 204, description: 'Project deleted successfully', examples: [[
        'message' => 'Project deleted successfully',
    ]])]
    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ], 204);
    }

    /**
     * Display a listing of trashed projects.
     */
    #[Response(status: 200, description: 'Paginated list of the user\'s trashed projects', examples: [[
        'data' => [['id' => 1, 'name' => 'Work', 'description' => 'Work projects', 'color' => 'blue', 'created_at' => '2026-08-06T00:00:00.000000Z']],
        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'path' => 'http://localhost:8000/api/v1/projects/trashed', 'per_page' => 10, 'to' => 1, 'total' => 1],
    ]])]
    public function trashed(Request $request): AnonymousResourceCollection
    {
        $trashedProjects = $request->user()->projects()->onlyTrashed()->latest()->paginate(10);

        return ProjectResource::collection($trashedProjects);
    }

    /**
     * Restore the specified project.
     */
    #[Response(status: 200, description: 'Project restored successfully', examples: [[
        'data' => ['id' => 1, 'name' => 'Work', 'description' => 'Work projects', 'color' => 'blue', 'created_at' => '2026-08-06T00:00:00.000000Z'],
    ]])]
    public function restore(Project $project): ProjectResource
    {
        $this->authorize('restore', $project);
        $project->restore();

        return new ProjectResource($project);
    }
}

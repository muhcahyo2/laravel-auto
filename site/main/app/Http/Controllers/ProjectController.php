<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateEnvProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Support\Str;
use App\Models\Project;
use App\Models\ProjectCommands;
use App\Services\ProjectService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::all();
        return response()->json([
            'data' => $projects,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();
        $validated['path'] = '/var/www/html/' . $validated['subdomain'];

        // cek apakah modenya express and frontend, jika iya cari port yang tidak dipakai terlebih dulu
        $port = null;
        if($validated['mode'] == Project::MODE_EXPRESS_AND_FRONTEND){
            
        }

        DB::beginTransaction();
        try {
            $validated['dbname'] = Str::random();
            $validated['dbuser'] = Str::random();
            $validated['dbpassword'] = Str::random();
    
            $project = new Project;
            $project->name          = $validated['name'];
            $project->subdomain     = $validated['subdomain'];
            
            $project->public_root   = $validated['public_root'];
            $project->port          = $port;
            $project->path          = $validated['path'];
            $project->repository_url= $validated['repository_url'];
            $project->mode          = $validated['mode'];
    
            $project->dbname        = $validated['dbname'];
            $project->dbuser        = $validated['dbuser'];
            $project->dbpassword    = $validated['dbpassword'];
            $project->save();
            
            $commands = ProjectCommands::TEMPLATE_COMMANDS[$project->mode];

            $project_id = $project->id;

            foreach ($commands as $key => $command) {
                $projectCommands = new ProjectCommands();
                $projectCommands->commands = $command;
                $projectCommands->project_id = $project_id;
                $projectCommands->order = $key;
                $projectCommands->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('failed add project', [
                'data' => $validated
            ]);
            Log::debug('Error inserting project', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'failed add project',
            ], 500));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Projects created'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, int $id)
    {
        $project = $project->with('commands')->find($id);

        if(empty($project)){
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'project not found',
            ],404));
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        //
    }

    public function publish(Project $project, int $id)
    {
        $project = $project->find($id);

        if(empty($project)){
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'project not found',
            ],404));
        }
        
        $projectService = new ProjectService($project);

        $projectService->generateDB();

        $projectService->generateConfigNginx();

        $projectService->runCommand();

        return response()->json([
            'success' => true,
            'message' => 'success publish',
        ], 200);

    }

    public function pullCode(Project $project, int $id)
    {
        $project = $project->find($id);

        if(empty($project)){
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'project not found',
            ],404));
        }

        $projectService = new ProjectService($project);

        $projectService->pullProject();

        return response()->json([
            'success' => true,
            'message' => 'success pull code',
        ], 200);
        
    }

    public function copyEnv(Project $project, int $id)
    {
        $project = $project->find($id);

        if(empty($project)){
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'project not found',
            ],404));
        }

        $projectService = new ProjectService($project);

        $projectService->createEnvFromExample();

        return response()->json([
            'success' => true,
            'message' => 'success copy .env from .env.example',
        ], 200);
    }

    public function showEnv(Project $project, int $id)
    {
        $project = $project->find($id);

        if(empty($project)){
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'project not found',
            ],404));
        }

        $path = $project->path;

        $env = ProjectService::parseEnvFile($path, '.env');

        return response()->json([
            'success' => true,
            'data' => $env
        ], 200);

    }

    public function updateEnv(UpdateEnvProjectRequest $request, Project $project, int $id)
    {
        $project = $project->find($id);

        if(empty($project)){
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'project not found',
            ],404));
        }

        $data = $request->input();

        $path = $project->path;

        $updatedEnv = ProjectService::writeEnv($data, $path, '.env');

        return response()->json([
            'success' => true,
            'message' => 'Success update env'
        ], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectCommandRequest;
use App\Models\Project;
use App\Models\ProjectCommands;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProjectCommandController extends Controller
{
    public function store(StoreProjectCommandRequest $request, Project $project, int $id)
    {
        $project = $project->find($id);

        if(empty($project)){
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'project not found',
            ],404));
        }
        
        $data = $request->validated();
        $lastOrder = ProjectCommands::where('project_id', $project->id)->max('order');
        
        $command = new ProjectCommands();
        $command->order = $lastOrder +1;
        $command->commands = $data['commands'];
        $command->project_id = $project->id;
        $command->save();

        return response()->json([
            'success' => true,
            'message' => 'success add command'
        ], 201);
    }
}

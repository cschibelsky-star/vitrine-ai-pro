<?php

namespace App\Http\Controllers;

use App\Models\AiAgent;
use App\Services\Ai\AiExecutionService;
use Illuminate\Http\Request;

class AiRunController extends Controller
{
    public function create()
    {
        $agents = AiAgent::query()->orderBy('name')->get();

        return view('ai.run', compact('agents'));
    }

    public function store(Request $request, AiExecutionService $service)
    {
        $data = $request->validate([
            'ai_agent_id' => ['required', 'exists:ai_agents,id'],
            'prompt' => ['required', 'string', 'min:5'],
        ]);

        $agent = AiAgent::findOrFail($data['ai_agent_id']);
        $execution = $service->execute($agent, $data['prompt']);

        return view('ai.result', compact('agent', 'execution'));
    }
}

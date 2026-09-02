<?php

namespace App\Http\Controllers;

use App\Models\AiAgent;
use App\Services\Ai\AiExecutionService;
use App\Services\Ai\AiRoutingService;
use Illuminate\Http\Request;

class AiRunController extends Controller
{
    public function create()
    {
        $agents = AiAgent::query()->orderBy('name')->get();

        return view('ai.run', compact('agents'));
    }

    public function store(Request $request, AiExecutionService $service, AiRoutingService $router)
    {
        $data = $request->validate([
            'ai_agent_id' => ['required', 'exists:ai_agents,id'],
            'prompt' => ['required', 'string', 'min:5'],
        ]);

        $agent = AiAgent::findOrFail($data['ai_agent_id']);

        $execution = $agent->slug === 'marketing-ia'
            ? $router->execute($agent, $data['prompt'])
            : $service->execute($agent, $data['prompt']);

        return view('ai.result', compact('agent', 'execution'));
    }
}

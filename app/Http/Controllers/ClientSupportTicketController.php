<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;

class ClientSupportTicketController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isClient') || ! $user->isClient()) {
            abort(403);
        }

        if (! $user->company_id) {
            abort(403, 'Usuário cliente sem empresa vinculada.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:Baixa,Média,Alta,Urgente'],
        ]);

        SupportTicket::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'Média',
            'status' => 'Aberto',
            'opened_at' => now(),
        ]);

        return redirect('/cliente')->with('success', 'Chamado aberto com sucesso.');
    }
}

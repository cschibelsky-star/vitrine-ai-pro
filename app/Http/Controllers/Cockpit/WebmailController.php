<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use App\Services\CockpitWebmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class WebmailController extends Controller
{
    public function __construct(private CockpitWebmailService $webmail)
    {
    }

    public function index(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('cockpit.login');
        }

        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $enabled = (bool) config('cockpit-webmail.enabled', false);
        $accounts = collect(config('cockpit-webmail.accounts', []));
        $accountKey = (string) $request->query('account', $accounts->keys()->first() ?: 'vendas');
        abort_unless($accounts->has($accountKey), 404);

        $folder = (string) $request->query('folder', 'INBOX');
        $messages = [];
        $folders = ['INBOX'];
        $connectionError = null;

        if ($enabled) {
            try {
                $folders = $this->webmail->folders($accountKey);
                $messages = $this->webmail->messages($accountKey, $folder);
            } catch (Throwable $e) {
                report($e);
                $connectionError = $e->getMessage();
            }
        }

        return view('cockpit.webmail', compact(
            'enabled',
            'accounts',
            'accountKey',
            'folder',
            'folders',
            'messages',
            'connectionError'
        ));
    }

    public function show(Request $request, int $uid)
    {
        if (! Auth::check()) {
            return response()->json(['ok' => false, 'error' => 'Sessão expirada. Faça login novamente no Cockpit.'], 401);
        }

        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $accountKey = (string) $request->query('account', 'vendas');
        $folder = (string) $request->query('folder', 'INBOX');
        $message = null;
        $error = null;

        try {
            $message = $this->webmail->message($accountKey, $uid, $folder);
        } catch (Throwable $e) {
            report($e);
            $error = $e->getMessage();
        }

        return response()->json([
            'ok' => $message !== null,
            'message' => $message,
            'error' => $error,
        ]);
    }

    public function send(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('cockpit.login');
        }

        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $data = $request->validate([
            'account' => ['required', 'string'],
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        try {
            $this->webmail->send($data['account'], $data['to'], $data['subject'], $data['body']);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'send' => 'Não foi possível enviar a mensagem: '.$e->getMessage(),
            ]);
        }

        return back()->with('status', 'Mensagem enviada com sucesso.');
    }

    public function probe(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('cockpit.login');
        }

        $user = Auth::user();
        abort_unless($user && ($user->is_active ?? true) && $user->isAdmin(), 403);

        $accountKey = (string) $request->query('account', 'vendas');

        try {
            return response()->json([
                'ok' => true,
                'result' => $this->webmail->probe($accountKey),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 503);
        }
    }

}

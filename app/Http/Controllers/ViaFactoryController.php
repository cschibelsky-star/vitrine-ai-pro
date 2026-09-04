<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Factory\Intake\Services\FactoryViaIntakeService;
use App\Factory\Models\FactoryExecution;
use App\Factory\Models\FactoryProject;
use App\Factory\Production\Services\ProductionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

final class ViaFactoryController extends Controller
{
    public function context(Request $request, ProductionStatusService $productionStatus): JsonResponse
    {
        $this->assertAdmin($request);
        return response()->json([
            'ok' => true,
            'context' => $this->buildOperationalContext($request, $productionStatus, $request->only(['url','path','title','module','resource'])),
            'capabilities' => [
                'consultar_saude','listar_projetos','listar_execucoes','consultar_producao','consultar_release','gerar_plano',
                'criar_arquitetura_com_confirmacao','produzir_solicitacao_com_confirmacao','finalizar_projeto_com_confirmacao',
                'produzir_enterprise_com_confirmacao','executar_smart_qa','executar_qa_modulo',
                'simular_instalacao_final_com_confirmacao','simular_instalacao_real_com_confirmacao',
                'preparar_intake_via','executar_intake_aprovado_com_confirmacao',
            ],
        ]);
    }

    public function chat(Request $request, ProductionStatusService $productionStatus, FactoryViaIntakeService $intakeService): JsonResponse
    {
        $this->assertAdmin($request);
        $validated = $request->validate([
            'message' => ['required','string','max:20000'],
            'history' => ['sometimes','array','max:30'],
            'history.*.role' => ['required_with:history', Rule::in(['user','assistant'])],
            'history.*.content' => ['required_with:history','string','max:12000'],
            'sessionId' => ['sometimes','nullable','string','max:160'],
            'context' => ['sometimes','array'],
        ]);
        $message = trim($validated['message']);
        $context = $this->buildOperationalContext($request, $productionStatus, is_array($validated['context'] ?? null) ? $validated['context'] : []);

        if (preg_match('/\bfactory\b.*\b(construa|construir|crie|criar|desenvolva|desenvolver)\b\s*(.+)/iu', $message, $matches)) {
            $requested = trim($matches[2] ?? '');
            if (mb_strlen($requested) >= 10) {
                try {
                    $prepared = $intakeService->prepare($requested, (int) $request->user()->getAuthIdentifier());
                    return response()->json([
                        'ok' => true,
                        'answer' => 'Preparei a análise da solicitação antes de construir. Revise Perfil/DNA, Prompt Mestre, riscos e decisões abertas. Se estiver correto, confirme EXECUTAR para iniciar construção, QA e dry-run.',
                        'mode' => 'factory-intake-ready',
                        'factory_connected' => true,
                        'requires_confirmation' => true,
                        'confirmation_token' => 'EXECUTAR',
                        'action' => 'execute_intake',
                        'payload' => ['project_id' => data_get($prepared, 'project.id')],
                        'result' => $prepared,
                    ]);
                } catch (Throwable $e) {
                    Log::warning('via.factory.intake_chat_prepare_failed', ['error' => $e->getMessage(), 'user_id' => $request->user()->getAuthIdentifier()]);
                    return response()->json(['ok' => false, 'answer' => $e->getMessage(), 'mode' => 'factory-intake-error'], 422);
                }
            }
        }

        if ($answer = $this->answerOperationalIntent($message, $context)) {
            return response()->json($answer);
        }
        $token = $this->coreAiToken();
        if ($token !== '') {
            try {
                $projectId = $this->viaProjectId();
                $prompt = "Mensagem do usuário:\n{$message}\n\nContexto operacional da Factory:\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (! empty($validated['history'])) {
                    $prompt .= "\n\nHistórico recente:\n".json_encode($validated['history'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $response = Http::withToken($token)
                    ->withHeaders(['X-Vitrine-Project' => $projectId])
                    ->timeout(75)
                    ->acceptJson()
                    ->asJson()
                    ->post($this->coreAiHubUrl(), [
                        'project_id' => $projectId,
                        'profile' => 'balanced',
                        'system' => 'Você é a VIA, supervisora operacional da Vitrine IA Pro Factory. Responda em português brasileiro, use somente o contexto fornecido para fatos operacionais e nunca execute ações sensíveis sem confirmação explícita.',
                        'prompt' => $prompt,
                        'options' => ['temperature' => 0.2],
                    ]);
                if ($response->successful() && is_string(data_get($response->json(), 'data.content'))) {
                    return response()->json([
                        'answer' => (string) data_get($response->json(), 'data.content'),
                        'mode' => 'core-ai-dev-hub',
                        'factory_connected' => true,
                        'operational_context' => $context,
                        'ai' => [
                            'provider' => data_get($response->json(), 'data.provider'),
                            'model' => data_get($response->json(), 'data.model'),
                            'usage' => data_get($response->json(), 'data.usage'),
                            'request_id' => data_get($response->json(), 'data.request_id'),
                        ],
                    ]);
                }
                Log::warning('via.factory.ai_dev_hub_failed', ['status' => $response->status(), 'user_id' => $request->user()->getAuthIdentifier()]);
            } catch (Throwable $e) {
                Log::warning('via.factory.ai_dev_hub_connection_failed', ['error' => $e->getMessage(), 'user_id' => $request->user()->getAuthIdentifier()]);
            }
        } else {
            Log::warning('via.factory.ai_dev_hub_token_missing', ['user_id' => $request->user()->getAuthIdentifier()]);
        }
        return response()->json(['answer' => $this->fallbackAnswer($context), 'mode' => 'factory-fallback', 'factory_connected' => true]);
    }

    public function transcribe(Request $request): JsonResponse
    {
        $this->assertAdmin($request);
        $validated = $request->validate([
            'audio' => ['required', 'file', 'max:10240'],
        ]);

        $audio = $validated['audio'];
        $mime = strtolower((string) ($audio->getMimeType() ?: $audio->getClientMimeType() ?: 'audio/webm'));
        $allowed = ['audio/webm','video/webm','audio/ogg','audio/mp4','audio/mpeg','audio/wav','audio/x-wav','audio/aac','application/octet-stream'];
        if (! in_array($mime, $allowed, true)) {
            return response()->json(['ok'=>false,'error'=>'unsupported_audio_type','mime'=>$mime], 422);
        }

        $bytes = file_get_contents($audio->getRealPath());
        if ($bytes === false || $bytes === '') {
            return response()->json(['ok'=>false,'error'=>'empty_audio'], 422);
        }

        try {
            $response = Http::timeout(90)
                ->withHeaders(['Content-Type'=>$mime,'Accept'=>'application/json'])
                ->withBody($bytes, $mime)
                ->post($this->viaServiceUrl().'/api/transcribe');
            $payload = $response->json();
        } catch (Throwable $e) {
            Log::warning('via.factory.transcription_connection_failed', ['error'=>$e->getMessage(),'user_id'=>$request->user()->getAuthIdentifier()]);
            return response()->json(['ok'=>false,'error'=>'via_transcription_service_unreachable','message'=>'O serviço central de voz da VIA não respondeu.'], 502);
        }

        if (! $response->successful() || ! is_array($payload) || empty($payload['ok'])) {
            return response()->json(['ok'=>false,'error'=>'via_transcription_failed','message'=>is_array($payload) ? ($payload['error'] ?? 'Falha na transcrição da VIA.') : 'Falha na transcrição da VIA.'], 502);
        }

        return response()->json(['ok'=>true,'text'=>mb_substr(trim((string)($payload['text'] ?? '')),0,4000)]);
    }

    public function action(Request $request, FactoryViaIntakeService $intakeService): JsonResponse
    {
        $this->assertAdmin($request);
        $validated = $request->validate([
            'action' => ['required', Rule::in(['factory_health','production_status','release_status','plugins','ai_plan','architect_request','produce_request','finish_project','produce_enterprise','smart_qa2','qa_module','install_final_dry_run','real_install_dry_run','prepare_intake','execute_intake'])],
            'payload' => ['sometimes','array'],
            'confirm' => ['sometimes','nullable','string','max:20'],
        ]);
        $action = $validated['action'];
        $payload = is_array($validated['payload'] ?? null) ? $validated['payload'] : [];
        $confirm = (string)($validated['confirm'] ?? '');

        if ($action === 'prepare_intake') {
            try {
                $prepared = $intakeService->prepare(
                    $this->requiredText($payload, 'request', 10),
                    (int) $request->user()->getAuthIdentifier(),
                );

                return response()->json([
                    'ok' => true,
                    'action' => $action,
                    'requires_confirmation' => true,
                    'confirmation_token' => 'EXECUTAR',
                    'answer' => 'Análise preparada. Revise Perfil/DNA, Prompt Mestre, riscos e decisões abertas. Para iniciar construção, confirme EXECUTAR com o project_id retornado.',
                    'result' => $prepared,
                ]);
            } catch (Throwable $e) {
                Log::warning('via.factory.intake_prepare_failed', ['error' => $e->getMessage(), 'user_id' => $request->user()->getAuthIdentifier()]);
                return response()->json(['ok' => false, 'action' => $action, 'answer' => $e->getMessage()], 422);
            }
        }

        if ($action === 'execute_intake') {
            if ($confirm !== 'EXECUTAR') {
                return response()->json([
                    'ok' => false,
                    'requires_confirmation' => true,
                    'answer' => 'A análise está pronta, mas a construção altera artefatos. Confirme explicitamente com EXECUTAR.',
                    'action' => $action,
                    'payload' => $payload,
                ], 409);
            }

            try {
                $projectId = (int) ($payload['project_id'] ?? 0);
                abort_if($projectId <= 0, 422, 'project_id inválido.');
                $result = $intakeService->executeApproved($projectId, (int) $request->user()->getAuthIdentifier());

                return response()->json([
                    'ok' => true,
                    'action' => $action,
                    'answer' => 'Construção, Smart QA e dry-run concluídos. O projeto está no checkpoint humano de release.',
                    'result' => $result,
                ]);
            } catch (Throwable $e) {
                Log::warning('via.factory.intake_execute_failed', ['error' => $e->getMessage(), 'user_id' => $request->user()->getAuthIdentifier()]);
                return response()->json(['ok' => false, 'action' => $action, 'answer' => $e->getMessage()], 422);
            }
        }

        $definition = match ($action) {
            'factory_health' => ['factory:health', [], false],
            'production_status' => ['factory:production-status', [], false],
            'release_status' => ['factory:release-status', [], false],
            'plugins' => ['factory:plugins', [], false],
            'ai_plan' => ['factory:ai-plan', ['prompt' => $this->words($this->requiredText($payload,'prompt',10))], false],
            'architect_request' => ['factory:architect-request', ['request' => $this->words($this->requiredText($payload,'request',10))], true],
            'produce_request' => ['factory:produce-request', ['request' => $this->words($this->requiredText($payload,'request',10)), '--approved' => true], true],
            'finish_project' => ['factory:finish-project', ['request' => $this->words($this->requiredText($payload,'request',10))], true],
            'produce_enterprise' => ['factory:produce-enterprise', ['product' => $this->allowedProduct($this->requiredText($payload,'product',3))], true],
            'smart_qa2' => ['factory:smart-qa2', [], false],
            'qa_module' => ['factory:qa-module', ['slug' => $this->requiredSlug($payload,'slug')], false],
            'install_final_dry_run' => ['factory:install-final', ['blueprint' => $this->requiredSlug($payload,'blueprint'), '--dry-run' => true], true],
            'real_install_dry_run' => ['factory:real-install', ['blueprint' => $this->requiredSlug($payload,'blueprint'), '--dry-run' => true], true],
        };
        [$command,$arguments,$sensitive] = $definition;
        if ($sensitive && $confirm !== 'EXECUTAR') {
            return response()->json(['ok'=>false,'requires_confirmation'=>true,'answer'=>'Esta ação criará ou alterará artefatos da Factory. Confirme explicitamente para executar.','action'=>$action,'payload'=>$payload],409);
        }
        $result = $this->runArtisan($command,$arguments);
        Log::info('via.factory.action',['action'=>$action,'command'=>$command,'exit_code'=>$result['exit_code'],'user_id'=>$request->user()->getAuthIdentifier()]);
        return response()->json(['ok'=>$result['exit_code']===0,'action'=>$action,'answer'=>$result['exit_code']===0?"Ação concluída pela Factory.\n\n".$result['output']:"A Factory não concluiu a ação.\n\n".$result['output'],'result'=>$result],$result['exit_code']===0?200:422);
    }

    private function answerOperationalIntent(string $message, array $context): ?array
    {
        $n = mb_strtolower($message,'UTF-8');
        if (preg_match('/\b(status|sa[uú]de|situa[cç][aã]o)\b.*\bfactory\b|\bfactory\b.*\b(status|sa[uú]de|situa[cç][aã]o)\b/u',$n)) return $this->answer($this->factoryStatusAnswer($context));
        if (preg_match('/\b(projetos?|produto(?:s)?)\b/u',$n)) return $this->answer($this->projectsAnswer($context));
        if (preg_match('/\b(execu[cç][oõ]es?|tarefas?|jobs?)\b/u',$n)) return $this->answer($this->executionsAnswer($context));
        if (preg_match('/\b(produ[cç][aã]o|release|vers[aã]o)\b/u',$n)) return $this->answer($this->productionAnswer($context));
        if (preg_match('/\b(ecossistema|servidor|vps|servi[cç]os?)\b/u',$n)) return $this->answer($this->ecosystemAnswer($context));
        if (preg_match('/(?:planeje|planejar|crie um plano|gere um plano)\s+(?:para\s+)?(.+)/iu',$message,$m) && mb_strlen(trim($m[1]))>=10) {
            $r=$this->runArtisan('factory:ai-plan',['prompt'=>$this->words(trim($m[1]))]);
            return ['answer'=>$r['exit_code']===0?"Plano gerado pela Factory.\n\n".$r['output']:"Não consegui gerar o plano.\n\n".$r['output'],'mode'=>'factory-action','factory_connected'=>true,'action'=>'ai_plan','ok'=>$r['exit_code']===0];
        }
        if (preg_match('/(?:execute|rode|fa[cç]a)\s+(?:o\s+)?smart\s*qa(?:\s*2)?/iu',$message)) {
            $r=$this->runArtisan('factory:smart-qa2');
            return ['answer'=>$r['exit_code']===0?"Smart QA concluído com sucesso.\n\n".$r['output']:"O Smart QA encontrou falhas.\n\n".$r['output'],'mode'=>'factory-action','factory_connected'=>true,'action'=>'smart_qa2','ok'=>$r['exit_code']===0];
        }
        if (preg_match('/(?:execute|rode|fa[cç]a)\s+(?:o\s+)?qa\s+(?:do\s+)?m[oó]dulo\s+([a-z0-9_-]+)/iu',$message,$m)) {
            $r=$this->runArtisan('factory:qa-module',['slug'=>strtolower(trim($m[1]))]);
            return ['answer'=>$r['exit_code']===0?"QA do módulo concluído com sucesso.\n\n".$r['output']:"O QA do módulo encontrou falhas.\n\n".$r['output'],'mode'=>'factory-action','factory_connected'=>true,'action'=>'qa_module','ok'=>$r['exit_code']===0];
        }
        $patterns = [
            ['/(?:simule|simular|teste)\s+(?:a\s+)?instala[cç][aã]o\s+final\s+(?:do\s+)?(?:blueprint\s+)?([a-z0-9_-]+)/iu','install_final_dry_run','blueprint','Posso simular a instalação final sem alterar o sistema ativo. Confirme para executar o dry-run.'],
            ['/(?:simule|simular|teste)\s+(?:a\s+)?instala[cç][aã]o\s+real\s+(?:do\s+)?(?:blueprint\s+)?([a-z0-9_-]+)/iu','real_install_dry_run','blueprint','Posso simular a instalação do código real sem modificar arquivos ativos. Confirme para executar o dry-run.'],
            ['/(?:produza|gerar|gere)\s+(?:o\s+)?(?:produto\s+)?enterprise\s+([a-z0-9_-]+)/iu','produce_enterprise','product','Posso produzir o pacote Enterprise do produto informado. A ação gera builds completos e exige confirmação.'],
        ];
        foreach($patterns as [$regex,$action,$key,$text]) if(preg_match($regex,$message,$m)) return $this->confirmation($action,[$key=>strtolower(trim($m[1]))],$text);
        foreach([
            ['/(?:produza|produzir|gere o pacote|crie o pacote)\s+(?:um\s+)?(?:sistema|produto|pacote)?\s*(?:para|de)?\s*(.+)/iu','produce_request','Posso produzir essa solicitação na área segura da Factory. A ação gera artefatos e precisa da sua confirmação.'],
            ['/(?:finalize|finalizar|conclua|concluir)\s+(?:o\s+)?(?:projeto|sistema)?\s*(?:para|de)?\s*(.+)/iu','finish_project','Posso executar a finalização completa, incluindo produção e real build. Essa ação exige confirmação explícita.'],
            ['/(?:crie|gere|monte)\s+(?:uma\s+)?(?:arquitetura|blueprint)\s*(?:para|de)?\s*(.+)/iu','architect_request','Posso criar a arquitetura e o blueprint na Factory. Essa ação grava artefatos e precisa da sua confirmação.'],
        ] as [$regex,$action,$text]) if(preg_match($regex,$message,$m) && mb_strlen(trim($m[1]))>=10) return $this->confirmation($action,['request'=>trim($m[1])],$text);
        return null;
    }

    private function answer(string $text): array { return ['answer' => $text, 'mode' => 'factory-operational', 'factory_connected' => true]; }
    private function confirmation(string $action, array $payload, string $text): array { return ['answer' => $text, 'mode' => 'factory-confirmation', 'factory_connected' => true, 'requires_confirmation' => true, 'action' => $action, 'payload' => $payload]; }

    private function buildOperationalContext(Request $request, ProductionStatusService $productionStatus, array $pageContext = []): array
    {
        $factory=['projects_total'=>0,'projects_by_status'=>[],'executions_total'=>0,'executions_by_status'=>[],'recent_projects'=>[],'recent_executions'=>[],'production'=>[]];
        try {
            $factory['projects_total']=FactoryProject::query()->count();
            $factory['projects_by_status']=FactoryProject::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status')->map(fn($v) => (int) $v)->all();
        } catch (Throwable $e) {
            $factory['projects_by_status']=[];
        }
        try {
            $factory['recent_projects']=FactoryProject::query()->latest('id')->limit(6)->get(['id','uuid','name','slug','status','updated_at'])->map(fn(FactoryProject $p) => ['id'=>$p->id,'uuid'=>$p->uuid,'name'=>$p->name,'slug'=>$p->slug,'status'=>$p->status,'updated_at'=>optional($p->updated_at)->toISOString()])->all();
            $factory['executions_total']=FactoryExecution::query()->count();
            $factory['executions_by_status']=FactoryExecution::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status')->map(fn($v) => (int) $v)->all();
            $factory['recent_executions']=FactoryExecution::query()->with('project:id,name')->latest('id')->limit(8)->get(['id','uuid','factory_project_id','name','status','duration_ms','created_at'])->map(fn(FactoryExecution $e) => ['id'=>$e->id,'uuid'=>$e->uuid,'name'=>$e->name,'project'=>$e->project?->name,'status'=>$e->status,'duration_ms'=>$e->duration_ms,'created_at'=>optional($e->created_at)->toISOString()])->all();
            $factory['production']=$productionStatus->status();
        } catch (Throwable $e) { $factory['error']=$e->getMessage(); }
        $ecosystem=['status'=>'unavailable','summary'=>[],'services'=>[]];
        try { $r=Http::timeout(6)->acceptJson()->get($this->vaeBaseUrl().'/api/vae/ecosystem'); if($r->successful()) $ecosystem=$r->json(); } catch(Throwable $e){ $ecosystem['error']=$e->getMessage(); }
        return ['source'=>'Vitrine IA Pro Factory','generated_at'=>now()->toISOString(),'user'=>['id'=>$request->user()->getAuthIdentifier(),'name'=>$request->user()->name,'role'=>$request->user()->role],'page'=>['url'=>$pageContext['url']??$request->headers->get('referer'),'path'=>$pageContext['path']??null,'title'=>$pageContext['title']??null,'module'=>$pageContext['module']??'Factory','resource'=>$pageContext['resource']??null],'factory'=>$factory,'ecosystem'=>$ecosystem];
    }

    private function factoryStatusAnswer(array $c): string { $f=$c['factory'];$e=$c['ecosystem'];$s=$e['summary']??[];return sprintf("A Factory está conectada.\nProjetos: %d.\nExecuções: %d.\nProdução: %s.\nEcossistema: %s (%d online, %d degradados, %d offline).",(int)($f['projects_total']??0),(int)($f['executions_total']??0),(string)($f['production']['status']??'não informado'),(string)($e['status']??'indisponível'),(int)($s['online']??0),(int)($s['degraded']??0),(int)($s['offline']??0)); }
    private function projectsAnswer(array $c): string { $f=$c['factory'];$p=$f['recent_projects']??[];$l=[sprintf('A Factory possui %d projeto(s).',(int)($f['projects_total']??0))];if(!$p)$l[]='Ainda não há projetos registrados na tabela operacional.';else{ $l[]='Projetos mais recentes:';foreach($p as $x)$l[]=sprintf('• %s — %s',$x['name']?:$x['slug'],$x['status']?:'sem status');}return implode("\n",$l); }
    private function executionsAnswer(array $c): string { $f=$c['factory'];$p=$f['recent_executions']??[];$l=[sprintf('A Factory possui %d execução(ões) registrada(s).',(int)($f['executions_total']??0))];if(!$p)$l[]='Não há execuções recentes para apresentar.';else{ $l[]='Execuções mais recentes:';foreach($p as $x){$label=$x['name']?:($x['uuid']?:'Execução');$pr=$x['project']?' · '.$x['project']:'';$l[]=sprintf('• %s%s — %s',$label,$pr,$x['status']?:'sem status');}}return implode("\n",$l); }
    private function productionAnswer(array $c): string { $p=$c['factory']['production']??[];return sprintf("Motor de produção: %s.\nVersão: %s.\nStatus: %s.\nProdutos disponíveis: %s.\nArmazenamento: %s.",(string)($p['engine']??'não informado'),(string)($p['version']??'não informada'),(string)($p['status']??'não informado'),implode(', ',$p['products_available']??[])?:'nenhum informado',!empty($p['storage_ready'])?'pronto':'indisponível'); }
    private function ecosystemAnswer(array $c): string { $e=$c['ecosystem']??[];$s=$e['summary']??[];$l=[sprintf('Ecossistema: %s. %d serviço(s) configurado(s), %d online, %d degradado(s), %d offline.',(string)($e['status']??'indisponível'),(int)($s['configured']??0),(int)($s['online']??0),(int)($s['degraded']??0),(int)($s['offline']??0))];foreach(($e['services']??[]) as $x)$l[]=sprintf('• %s — %s',$x['label']??$x['id']??'Serviço',$x['status']??'desconhecido');return implode("\n",$l); }
    private function fallbackAnswer(array $c): string { return $this->factoryStatusAnswer($c)."\n\nO motor conversacional está temporariamente indisponível, mas continuo conectada aos dados operacionais da Factory."; }
    private function runArtisan(string $command, array $arguments = []): array { try{$code=Artisan::call($command,$arguments);return ['command'=>$command,'exit_code'=>$code,'output'=>trim(Artisan::output())?:'Comando concluído sem saída textual.'];}catch(Throwable $e){return ['command'=>$command,'exit_code'=>1,'output'=>$e->getMessage()];} }
    private function requiredSlug(array $p, string $k): string { $v=strtolower(trim((string)($p[$k]??'')));abort_unless((bool)preg_match('/^[a-z0-9][a-z0-9_-]{1,119}$/',$v),422,"O campo {$k} precisa conter um slug válido.");return $v; }
    private function requiredText(array $p, string $k, int $m): string { $v=trim((string)($p[$k]??''));abort_if(mb_strlen($v)<$m,422,"O campo {$k} precisa ter pelo menos {$m} caracteres.");return $v; }
    private function allowedProduct(string $v): string { $p=mb_strtolower(trim($v),'UTF-8');$a=['gov360'=>'gov360','governo'=>'gov360','guia_digital'=>'guia_digital','guia-digital'=>'guia_digital','guia'=>'guia_digital','portal_news'=>'portal_news','portal-news'=>'portal_news','news'=>'portal_news','tv_digital'=>'tv_digital','tv-digital'=>'tv_digital','tv'=>'tv_digital','sismed'=>'sismed'];abort_unless(isset($a[$p]),422,'Produto Enterprise não permitido. Use gov360, guia_digital, portal_news, tv_digital ou sismed.');return $a[$p]; }
    private function words(string $v): array { return preg_split('/\s+/u',trim($v),-1,PREG_SPLIT_NO_EMPTY)?:[]; }
    private function assertAdmin(Request $request): void { abort_unless($request->user() && $request->user()->isAdmin(),403); }
    private function coreAiHubUrl(): string { return rtrim((string) env('CORE_AI_HUB_URL', 'http://vitrine_core_web_hml/api/internal/ai-dev/chat'), '/'); }
    private function coreAiToken(): string { return trim((string) env('CENTRO_IA_INTERNAL_TOKEN', '')); }
    private function viaProjectId(): string { return trim((string) env('VIA_AI_PROJECT_ID', 'via-agent-hub')) ?: 'via-agent-hub'; }
    private function viaServiceUrl(): string { return rtrim((string) env('VIA_SERVICE_URL', 'http://via_hml_v04:3000'), '/'); }
    private function vaeBaseUrl(): string { return rtrim((string)(config('services.vae_core.url')?:'http://vae_core:3091'),'/'); }
}

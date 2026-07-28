<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Api\VendedoriaProNewsLeadController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class MemoryLeadInsertBuilder
{
    public array $inserted = [];

    public ?Throwable $failure = null;

    public function insertGetId(array $row): int
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        $this->inserted[] = $row;

        return count($this->inserted);
    }
}

final class MemoryLeadSchema
{
    public function __construct(private readonly array $columns)
    {
    }

    public function hasColumn(string $table, string $column): bool
    {
        return $table === 'leads' && in_array($column, $this->columns, true);
    }
}

final class MemoryLeadDatabase
{
    public function __construct(private readonly MemoryLeadInsertBuilder $builder)
    {
    }

    public function table(string $table): MemoryLeadInsertBuilder
    {
        if ($table !== 'leads') {
            throw new RuntimeException('Tabela não permitida pelo double.');
        }

        return $this->builder;
    }
}

final class RecordingExceptionHandler implements ExceptionHandler
{
    public array $reported = [];

    public function report(Throwable $e): void
    {
        $this->reported[] = $e;
    }

    public function shouldReport(Throwable $e): bool
    {
        return true;
    }

    public function render($request, Throwable $e): Response
    {
        return new Response('', 500);
    }

    public function renderForConsole($output, Throwable $e): void
    {
    }
}

final class VendedoriaProNewsLeadControllerTest extends TestCase
{
    private static $app;

    private MemoryLeadInsertBuilder $builder;

    private RecordingExceptionHandler $exceptionHandler;

    private array $columns = [
        'id',
        'external_id',
        'empresa',
        'contato',
        'telefone',
        'email',
        'cidade',
        'estado',
        'produto_interesse',
        'plano_sugerido',
        'valor_estimado',
        'origem_lead',
        'pagina_origem',
        'campanha',
        'consentimento_lgpd',
        'created_at',
        'updated_at',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $worktree = dirname(__DIR__, 2);
        $loader = require $worktree.'/vendor/autoload.php';
        $loader->setPsr4('App\\', $worktree.'/app');
        $appClassMap = [];

        foreach ($loader->getClassMap() as $class => $path) {
            if (! str_starts_with($class, 'App\\')) {
                continue;
            }

            $appOffset = strpos($path, '/app/');

            if ($appOffset !== false) {
                $appClassMap[$class] = $worktree.substr($path, $appOffset);
            }
        }

        $loader->addClassMap($appClassMap);

        self::$app = require $worktree.'/bootstrap/app.php';
        self::$app->make(Kernel::class)->bootstrap();
        self::$app->make('validator');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new MemoryLeadInsertBuilder();
        $this->exceptionHandler = new RecordingExceptionHandler();

        Schema::swap(new MemoryLeadSchema($this->columns));
        DB::swap(new MemoryLeadDatabase($this->builder));
        self::$app->instance(ExceptionHandler::class, $this->exceptionHandler);
    }

    public function test_lead_valido_retorna_201(): void
    {
        $response = $this->invokeController($this->validPayload());

        self::assertSame(201, $response['status']);
        self::assertTrue($response['json']['success']);
    }

    public function test_contato_obrigatorio_retorna_422(): void
    {
        $response = $this->invokeController($this->validPayload(['contato' => null]));

        self::assertSame(422, $response['status']);
        self::assertArrayHasKey('contato', $response['json']['errors']);
    }

    public function test_email_invalido_retorna_422(): void
    {
        $response = $this->invokeController($this->validPayload(['email' => 'email-invalido']));

        self::assertSame(422, $response['status']);
        self::assertArrayHasKey('email', $response['json']['errors']);
    }

    public function test_consentimento_lgpd_obrigatorio(): void
    {
        $response = $this->invokeController($this->validPayload(['consentimento_lgpd' => false]));

        self::assertSame(422, $response['status']);
        self::assertArrayHasKey('consentimento_lgpd', $response['json']['errors']);
    }

    public function test_honeypot_nao_persiste(): void
    {
        $response = $this->invokeController(['website' => 'bot.example']);

        self::assertSame(201, $response['status']);
        self::assertSame([], $this->builder->inserted);
    }

    public function test_portal_local_calcula_497(): void
    {
        $this->assertPlanValue('Portal Local', 497);
    }

    public function test_portal_automatizado_calcula_997(): void
    {
        $this->assertPlanValue('Portal Automatizado', 997);
    }

    public function test_licenciamento_institucional_calcula_1500(): void
    {
        $this->assertPlanValue('Licenciamento Institucional', 1500);
    }

    public function test_plano_desconhecido_mantem_valor_nulo(): void
    {
        $response = $this->invokeController($this->validPayload([
            'plano_sugerido' => 'Plano Desconhecido',
        ]));

        self::assertSame(201, $response['status']);
        self::assertNull($this->lastInsert()['valor_estimado']);
    }

    public function test_somente_colunas_existentes_sao_inseridas(): void
    {
        $response = $this->invokeController($this->validPayload([
            'observacoes' => 'Coluna deliberadamente ausente no schema simulado.',
        ]));

        self::assertSame(201, $response['status']);
        self::assertArrayNotHasKey('observacoes', $this->lastInsert());
        self::assertSame([], array_diff(array_keys($this->lastInsert()), $this->columns));
    }

    public function test_origem_pagina_e_campanha_sao_preenchidas(): void
    {
        $this->invokeController($this->validPayload());
        $insert = $this->lastInsert();

        self::assertSame('VendedorIA Pro News', $insert['origem_lead']);
        self::assertSame('https://example.test/noticia', $insert['pagina_origem']);
        self::assertSame('VendedorIA Pro News 1.1', $insert['campanha']);
    }

    public function test_pagina_origem_valida_e_aceita(): void
    {
        $pagina = str_repeat('a', 255);
        $response = $this->invokeController($this->validPayload(['pagina_origem' => $pagina]));

        self::assertSame(201, $response['status']);
        self::assertSame($pagina, $this->lastInsert()['pagina_origem']);
    }

    public function test_pagina_origem_ausente_usa_fallback(): void
    {
        $payload = $this->validPayload();
        unset($payload['pagina_origem']);

        $response = $this->invokeController($payload);

        self::assertSame(201, $response['status']);
        self::assertSame('Widget VendedorIA Pro News', $this->lastInsert()['pagina_origem']);
    }

    public function test_pagina_origem_acima_de_255_retorna_422(): void
    {
        $response = $this->invokeController($this->validPayload([
            'pagina_origem' => str_repeat('a', 256),
        ]));

        self::assertSame(422, $response['status']);
        self::assertArrayHasKey('pagina_origem', $response['json']['errors']);
    }

    public function test_pagina_origem_array_retorna_422(): void
    {
        $response = $this->invokeController($this->validPayload([
            'pagina_origem' => ['valor-invalido'],
        ]));

        self::assertSame(422, $response['status']);
        self::assertArrayHasKey('pagina_origem', $response['json']['errors']);
    }

    public function test_falha_de_banco_retorna_503(): void
    {
        $this->builder->failure = new RuntimeException('SQLSTATE interno');

        $response = $this->invokeController($this->validPayload());

        self::assertSame(503, $response['status']);
        self::assertFalse($response['json']['success']);
    }

    public function test_resposta_503_nao_expoe_mensagem_interna(): void
    {
        $this->builder->failure = new RuntimeException(
            'SQLSTATE[HY000] tabela leads coluna email host db.internal conexão recusada'
        );

        $response = $this->invokeController($this->validPayload());
        $body = json_encode($response['json'], JSON_THROW_ON_ERROR);

        self::assertSame(503, $response['status']);
        self::assertStringNotContainsString('SQLSTATE', $body);
        self::assertStringNotContainsString('leads', $body);
        self::assertStringNotContainsString('email', $body);
        self::assertStringNotContainsString('db.internal', $body);
        self::assertStringNotContainsString('conexão', $body);
        self::assertSame(
            'Não foi possível registrar o lead no momento. Tente novamente mais tarde.',
            $response['json']['message']
        );
    }

    public function test_falha_de_banco_e_reportada_internamente(): void
    {
        $exception = new RuntimeException('Falha de persistência simulada.');
        $this->builder->failure = $exception;

        $this->invokeController($this->validPayload());

        self::assertCount(1, $this->exceptionHandler->reported);
        self::assertSame($exception, $this->exceptionHandler->reported[0]);
    }

    public function test_rota_possui_throttle_30_1(): void
    {
        $route = collect(self::$app['router']->getRoutes()->getRoutes())
            ->first(fn ($candidate) => $candidate->uri() === 'api/vendedoria-pro-news/leads');

        self::assertNotNull($route);
        self::assertContains('POST', $route->methods());
        self::assertSame(
            VendedoriaProNewsLeadController::class.'@store',
            $route->getActionName()
        );
        self::assertContains('api', $route->gatherMiddleware());
        self::assertContains('throttle:30,1', $route->gatherMiddleware());
    }

    public function test_landing_e_widget_apontam_para_rota_correta(): void
    {
        $root = dirname(__DIR__, 2);
        $endpoint = '/api/vendedoria-pro-news/leads';

        self::assertStringContainsString(
            $endpoint,
            (string) file_get_contents($root.'/public/vendedoria-pro-news/index.html')
        );
        self::assertStringContainsString(
            $endpoint,
            (string) file_get_contents($root.'/public/assets/vendedoria-pro-news/widget.js')
        );
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'contato' => 'Pessoa de Teste',
            'email' => 'teste@example.test',
            'consentimento_lgpd' => true,
            'pagina_origem' => 'https://example.test/noticia',
        ], $overrides);
    }

    private function invokeController(array $payload): array
    {
        $request = Request::create(
            '/api/vendedoria-pro-news/leads',
            'POST',
            $payload,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );
        $response = (new VendedoriaProNewsLeadController())->store($request);

        return [
            'status' => $response->getStatusCode(),
            'json' => json_decode(
                (string) $response->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR
            ),
        ];
    }

    private function assertPlanValue(string $plan, int $expected): void
    {
        $response = $this->invokeController($this->validPayload([
            'plano_sugerido' => $plan,
        ]));

        self::assertSame(201, $response['status']);
        self::assertSame($expected, $this->lastInsert()['valor_estimado']);
    }

    private function lastInsert(): array
    {
        return $this->builder->inserted[array_key_last($this->builder->inserted)];
    }
}

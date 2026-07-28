<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Api\VendedoriaProNewsLeadController;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class CorsMemoryLeadInsertBuilder
{
    public array $inserted = [];

    public function insertGetId(array $row): int
    {
        $this->inserted[] = $row;

        return count($this->inserted);
    }
}

final class CorsMemoryLeadSchema
{
    public function hasColumn(string $table, string $column): bool
    {
        return $table === 'leads';
    }
}

final class CorsMemoryLeadDatabase
{
    public function __construct(private readonly CorsMemoryLeadInsertBuilder $builder)
    {
    }

    public function table(string $table): CorsMemoryLeadInsertBuilder
    {
        TestCase::assertSame('leads', $table);

        return $this->builder;
    }
}

final class VendedoriaProNewsCorsTest extends TestCase
{
    private const ENDPOINT = '/api/vendedoria-pro-news/leads';

    private const ALLOWED_ORIGIN = 'https://cliente.example';

    private const BLOCKED_ORIGIN = 'https://nao-permitido.example';

    private static $app;

    private static HttpKernel $httpKernel;

    private CorsMemoryLeadInsertBuilder $builder;

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
        self::$app->make(ConsoleKernel::class)->bootstrap();
        self::$app->make('validator');
        self::$httpKernel = self::$app->make(HttpKernel::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new CorsMemoryLeadInsertBuilder();

        Schema::swap(new CorsMemoryLeadSchema());
        DB::swap(new CorsMemoryLeadDatabase($this->builder));
        Config::set('cors', $this->corsConfig([self::ALLOWED_ORIGIN]));
    }

    public function test_post_same_origin_continues_working(): void
    {
        $response = $this->request('POST', self::ENDPOINT, $this->validPayload());

        self::assertSame(201, $response->getStatusCode());
        self::assertCount(1, $this->builder->inserted);
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_post_from_allowed_origin_returns_cors_origin(): void
    {
        $response = $this->request(
            'POST',
            self::ENDPOINT,
            $this->validPayload(),
            ['HTTP_ORIGIN' => self::ALLOWED_ORIGIN]
        );

        self::assertSame(201, $response->getStatusCode());
        self::assertSame(self::ALLOWED_ORIGIN, $response->headers->get('Access-Control-Allow-Origin'));
        self::assertContains('Origin', $response->getVary());
    }

    public function test_allowed_preflight_returns_204(): void
    {
        $response = $this->preflight(self::ALLOWED_ORIGIN);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame(self::ALLOWED_ORIGIN, $response->headers->get('Access-Control-Allow-Origin'));
        self::assertContains('Origin', $response->getVary());
    }

    public function test_preflight_allows_post_method(): void
    {
        $response = $this->preflight(self::ALLOWED_ORIGIN);

        self::assertStringContainsString('POST', (string) $response->headers->get('Access-Control-Allow-Methods'));
    }

    public function test_preflight_allows_required_headers(): void
    {
        $headers = (string) $this->preflight(self::ALLOWED_ORIGIN)
            ->headers
            ->get('Access-Control-Allow-Headers');

        self::assertStringContainsStringIgnoringCase('Content-Type', $headers);
        self::assertStringContainsStringIgnoringCase('Accept', $headers);
        self::assertStringContainsStringIgnoringCase('Origin', $headers);
    }

    public function test_blocked_origin_receives_no_cors_origin(): void
    {
        $response = $this->preflight(self::BLOCKED_ORIGIN);

        self::assertSame(204, $response->getStatusCode());
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_cors_never_allows_credentials(): void
    {
        $preflight = $this->preflight(self::ALLOWED_ORIGIN);
        $post = $this->request(
            'POST',
            self::ENDPOINT,
            $this->validPayload(),
            ['HTTP_ORIGIN' => self::ALLOWED_ORIGIN]
        );

        self::assertFalse($preflight->headers->has('Access-Control-Allow-Credentials'));
        self::assertFalse($post->headers->has('Access-Control-Allow-Credentials'));
    }

    public function test_endpoint_keeps_throttle_middleware(): void
    {
        $route = collect(self::$app['router']->getRoutes()->getRoutes())
            ->first(fn ($candidate) => $candidate->uri() === 'api/vendedoria-pro-news/leads');

        self::assertNotNull($route);
        self::assertContains('api', $route->gatherMiddleware());
        self::assertContains('throttle:30,1', $route->gatherMiddleware());
    }

    public function test_preflight_does_not_execute_controller(): void
    {
        $spy = new class extends VendedoriaProNewsLeadController
        {
            public int $calls = 0;

            public function store(Request $request)
            {
                $this->calls++;

                return response()->json(['unexpected' => true], 500);
            }
        };
        self::$app->instance(VendedoriaProNewsLeadController::class, $spy);

        try {
            $response = $this->preflight(self::ALLOWED_ORIGIN);
        } finally {
            self::$app->forgetInstance(VendedoriaProNewsLeadController::class);
        }

        self::assertSame(204, $response->getStatusCode());
        self::assertSame(0, $spy->calls);
    }

    public function test_preflight_does_not_persist_lead(): void
    {
        $this->preflight(self::ALLOWED_ORIGIN);

        self::assertSame([], $this->builder->inserted);
    }

    public function test_multiple_origins_are_interpreted(): void
    {
        Config::set('cors', $this->loadCorsConfig(
            'https://cliente1.example,https://cliente2.example',
            'https://app.example'
        ));

        $first = $this->preflight('https://cliente1.example');
        $second = $this->preflight('https://cliente2.example');

        self::assertSame('https://cliente1.example', $first->headers->get('Access-Control-Allow-Origin'));
        self::assertSame('https://cliente2.example', $second->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_spaces_empty_entries_and_wildcards_are_ignored(): void
    {
        $config = $this->loadCorsConfig(
            ' , https://cliente1.example , ,https://*.inseguro.example, https://cliente2.example ',
            'https://app.example'
        );

        self::assertSame(
            ['https://cliente1.example', 'https://cliente2.example'],
            $config['allowed_origins']
        );
    }

    public function test_fallback_to_app_url_works(): void
    {
        $config = $this->loadCorsConfig(null, 'https://app.example/');

        self::assertSame(['https://app.example'], $config['allowed_origins']);

        Config::set('cors', $config);
        $response = $this->preflight('https://app.example');

        self::assertSame('https://app.example', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_other_api_routes_do_not_receive_policy(): void
    {
        $response = $this->preflight(self::ALLOWED_ORIGIN, '/api/leads');

        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_admin_routes_do_not_receive_policy(): void
    {
        $response = $this->preflight(self::ALLOWED_ORIGIN, '/admin/vendedoria-pro-news');

        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_external_link_is_protected_against_opener_access(): void
    {
        $view = (string) file_get_contents(
            dirname(__DIR__, 2).'/resources/views/filament/pages/vendedor-ia-pro-news.blade.php'
        );

        self::assertMatchesRegularExpression(
            '/target="_blank"\s+rel="noopener noreferrer"/',
            $view
        );
    }

    private function request(
        string $method,
        string $uri,
        array $payload = [],
        array $server = []
    ): Response {
        $request = Request::create($uri, $method, $payload, [], [], array_merge([
            'HTTP_ACCEPT' => 'application/json',
        ], $server));

        $response = self::$httpKernel->handle($request);
        self::$httpKernel->terminate($request, $response);

        return $response;
    }

    private function preflight(string $origin, string $uri = self::ENDPOINT): Response
    {
        return $this->request('OPTIONS', $uri, [], [
            'HTTP_ORIGIN' => $origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Content-Type, Accept',
        ]);
    }

    private function validPayload(): array
    {
        return [
            'contato' => 'Pessoa de Teste',
            'email' => 'teste@example.test',
            'consentimento_lgpd' => true,
            'pagina_origem' => 'https://example.test/noticia',
        ];
    }

    private function corsConfig(array $origins): array
    {
        return [
            'paths' => ['api/vendedoria-pro-news/leads'],
            'allowed_methods' => ['POST', 'OPTIONS'],
            'allowed_origins' => $origins,
            'allowed_origins_patterns' => array_map(
                static fn (string $origin): string => '#^'.preg_quote($origin, '#').'\z#u',
                $origins
            ),
            'allowed_headers' => ['Content-Type', 'Accept', 'Origin'],
            'exposed_headers' => [],
            'max_age' => 0,
            'supports_credentials' => false,
        ];
    }

    private function loadCorsConfig(?string $origins, string $appUrl): array
    {
        $repository = Env::getRepository();
        $previousOrigins = $repository->get('VENDORIA_PRO_NEWS_ALLOWED_ORIGINS');
        $previousAppUrl = Config::get('app.url');

        if ($origins === null) {
            $repository->clear('VENDORIA_PRO_NEWS_ALLOWED_ORIGINS');
        } else {
            $repository->set('VENDORIA_PRO_NEWS_ALLOWED_ORIGINS', $origins);
        }

        Config::set('app.url', $appUrl);

        try {
            return require dirname(__DIR__, 2).'/config/cors.php';
        } finally {
            if ($previousOrigins === null) {
                $repository->clear('VENDORIA_PRO_NEWS_ALLOWED_ORIGINS');
            } else {
                $repository->set('VENDORIA_PRO_NEWS_ALLOWED_ORIGINS', $previousOrigins);
            }

            Config::set('app.url', $previousAppUrl);
        }
    }
}

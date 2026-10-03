# Vertex AI: runtime opt-in

Mantido desabilitado por padrão. Nenhuma geração real integra os testes.

## Configuração

VERTEX_AI_ENABLED=true, GOOGLE_CLOUD_PROJECT, GOOGLE_CLOUD_LOCATION e VERTEX_AI_DAILY_REQUEST_LIMIT inteiro positivo são obrigatórios para geração. Vídeo requer GOOGLE_VERTEX_VIDEO_GCS_URI (gs://bucket/prefix).
A migration 2026_10_03_160000_create_vertex_ai_daily_usage_table.php deve estar aplicada; sua ausência bloqueia as chamadas de geração.

GOOGLE_APPLICATION_CREDENTIALS aponta para JSON de conta de serviço RSA (mínimo 2048 bits), montado somente leitura no container por provisionamento externo. O Compose repassa o caminho, mas não monta o arquivo. A autenticação troca uma assinatura RS256 por token temporário no endpoint fixo do Google. Não aceita external_account, authorized_user nem metadata ADC. GOOGLE_VERTEX_ACCESS_TOKEN permanece como alternativa temporária.

## Consumo

Cada geração reserva uma tentativa no banco por projeto e dia UTC, antes do POST ao Vertex. O incremento condicional evita ultrapassar o limite entre workers que compartilham o banco. Todos os consumidores Vertex devem usar este adapter; o limite não é um teto monetário no Google Cloud nem cobre outros sistemas. Falhas HTTP e timeouts não liberam reservas porque o provedor pode ter aceitado a geração. Não há retry automático de geração.

## Resultado

Imagen retorna imagem salva no filesystem e asset_path. Veo retorna operation_id e Processando. VertexAiMediaAdapter::poll(operation_id, model) consulta a mesma operação sem gerar novamente. A URI GCS final permanece privada em metadata.vertex_gcs_uri.

O comando vertex:poll-media consulta apenas vídeos Vertex em Processando e persiste o estado final em AiMediaGeneration. Está agendado a cada minuto, sem sobreposição; exige scheduler Laravel operante. Falhas transitórias de consulta preservam o job para nova consulta, sem reenviar geração.

Pendente antes da ativação completa: disponibilizar acesso autorizado ao vídeo privado do GCS; provisionar montagem das credenciais, scheduler e validar permissões/billing/SKU. Não ativar só porque os testes simulados passaram.

## Validação

PHPUnit usa Http::fake e preventStrayRequests, SQLite em memória e credenciais temporárias geradas durante o teste. Não usa credenciais reais.

Referências:
- https://developers.google.com/identity/protocols/oauth2/service-account
- https://cloud.google.com/vertex-ai/generative-ai/docs/image/generate-images
- https://cloud.google.com/vertex-ai/generative-ai/docs/video/generate-videos-from-text

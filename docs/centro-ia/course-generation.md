# Centro IA — Capability `course_generation`

Endpoint interno para produtos da Vitrine IA Pro delegarem geração pedagógica ao Centro IA sem conhecer provedor, modelo ou chave externa.

## Endpoint

`POST /api/internal/centro-ia/execute`

## Autenticação

- `Authorization: Bearer <CENTRO_IA_INTERNAL_TOKEN>`
- `X-Vitrine-Project: cursos-ia-mvp`

O cabeçalho `X-Vitrine-Project` deve ser idêntico ao campo `project_id` do corpo.

## Corpo

```json
{
  "project_id": "cursos-ia-mvp",
  "capability": "course_generation",
  "input": {
    "system": "instruções do arquiteto pedagógico",
    "user": "pedido + fontes consolidadas",
    "response_format": "json",
    "temperature": 0.2
  }
}
```

## Resposta de sucesso

```json
{
  "ok": true,
  "project_id": "cursos-ia-mvp",
  "capability": "course_generation",
  "execution_id": 123,
  "agent_id": 10,
  "model": "modelo resolvido pelo Centro IA",
  "output_text": "{...}"
}
```

## Configuração do Core

```dotenv
CENTRO_IA_INTERNAL_TOKEN=
CENTRO_IA_COURSE_GENERATION_AGENT_ID=
CENTRO_IA_COURSE_GENERATION_AGENT_SLUG=
```

Configure apenas ID ou slug de um agente já cadastrado no Centro IA. O provedor, modelo, consumo e alertas continuam sob responsabilidade do `AiExecutionService` e dos cadastros do Core.

## Configuração do Cursos IA MVP

```dotenv
AI_ENABLED=1
AI_MODE=broker
AI_BROKER_URL=http://<servico-core>/api/internal/centro-ia/execute
AI_BROKER_TOKEN=<mesmo segredo interno>
AI_PROJECT_ID=cursos-ia-mvp
```

O hostname real do Core deve ser definido no ambiente de homologação; não deve ser hardcoded no repositório.

## Erros previstos

- `401 unauthorized`: token ausente/incorreto.
- `422 project_identity_mismatch`: header e `project_id` divergem.
- `422 capability_not_supported`: capability não registrada.
- `503 capability_agent_not_configured`: agente da capability ainda não configurado.
- `502 ai_execution_failed`: executor/provedor retornou falha.

## Regra arquitetural

Este endpoint não implementa OpenAI, Gemini ou qualquer outro provedor. Ele somente resolve a capability para um `AiAgent` do Centro IA e delega a execução para `App\Services\Ai\AiExecutionService`.

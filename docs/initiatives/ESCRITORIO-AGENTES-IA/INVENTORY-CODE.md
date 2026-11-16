# Inventário verificado — leitura de código (2026-11-16)

## Escopo
Auditoria de fontes do repositório `cschibelsky-star/vitrine-ai-pro` na branch de trabalho do Escritório. Esta etapa **não** fez consultas ao banco de dados, contêineres, estado online, filas ou às instâncias das outras aplicações. Não confundir arquivos existentes com agentes em execução.

| ID/fonte | Achado | Evidência | Nível |
|---|---|---|---|
| core-ai-agent | Modelo `App\\Models\\AiAgent` redireciona para `App\\Shared\\AI\\Models\\AiAgent` | `app/Models/AiAgent.php` | Código consultado |
| core-ai-seeder | Seeder com 9 entradas definidas: Editorial IA, Repórter IA, Comercial IA, Marketing IA, Turismo IA, Compras IA, Município IA, Cursos IA e SISMED IA | `database/seeders/AiAgentSeeder.php` | Definições de seed, **não** instâncias ativas |
| marketing-definitions | `MarketingAgentDefinition` permite relações com agente e tarefas, `depends_on`, `permissions`, `enabled`, `may_block_pipeline` | `app/Marketing/Infrastructure/Persistence/Models/MarketingAgentDefinition.php` | Código consultado |
| routing | `AiRoutingService` classifica por prompt e prioriza Roteia/Gemini/OpenAI/HeyGen conforme capacidade | `app/Services/Ai/AiRoutingService.php` | Código consultado |
| execution | `AiExecutionService` recebe provider/model override e executa integração Roteia e outros provedores | `app/Services/Ai/AiExecutionService.php` | Código consultado |
| hub-model | Protótipo `router.py` da HUB-IA-001 suporta namespaces `invision::`, `roteia::`, `avello::`, porém sem validação E2E com todos | `docs/initiatives/HUB-IA-001/router/router.py` | Protótipo isolado na branch ancestral |

## Não comprovado
- Quantidade, estado e identidade das instâncias reais dos agentes no banco.
- Skills efetivamente habilitadas por agente em runtime; uma descrição do seeder não é Skill operacional.
- Integrações funcionais e autenticação da VIA e da Factory a partir deste Escritório.
- Presença em salas, reuniões, horários, atividades em tempo real ou custos.
- Agentes existentes exclusivamente em outros repositórios: TV Sumaré, Vitrine Social Mídia, VIA e projetos separados exigem inspeção individual.

## Decisão de mapeamento
O painel MVP deve exibir `definição no código`, `runtime não verificado`, `fonte` e `habilidades sugeridas`; não pode rotular agente como online, em reunião ou em execução sem telemetria real.

## Próxima leitura necessária
Inventariar tabelas reais, contratos e endpoints seguros para listagem por ambiente/tenant, e os repositórios separados. Nenhuma conexão live está configurada nesta etapa.

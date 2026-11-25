# Escritório Virtual — Executor de IA dos agentes

## Entrega

O motor que faz os agentes **trabalharem de verdade**. Antes desta entrega, o
Escritório tinha a regra da VIA, os agentes, a sala operacional e a trilha de
auditoria — mas ninguém produzia: a entrega era digitada à mão.

- `AgentModelProvider` — o contrato do que um provedor de modelo precisa saber
  fazer (`name()`, `supports()`, `execute()`).
- `OpenAiCompatibleModelProvider` — adaptador para APIs no formato OpenAI,
  falando o mesmo protocolo que a plataforma já usa com o RoteirO
  (`{base}/v1/chat/completions`).
- `AgentTaskExecutor` — junta o registro de agentes (de onde vem o modelo) com o
  provedor, e monta o contexto do Escritório para o modelo.
- `AgentExecutionServiceProvider` — registra o executor no container. O teste
  sobrescreve o binding e passa um provedor falso.

## ⭐⭐⭐ As correções que a auditoria exigiu

Uma auditoria externa do PR #183 encontrou riscos reais. Todos foram corrigidos,
e **cada um tem teste que o trava**:

| Risco | Correção |
|---|---|
| **P0** — a credencial do Roteia servia de fallback genérico, e ia parar no endpoint de outro fornecedor | ⭐ Cada provedor tem a **sua** credencial (registro ou variável do fornecedor). A do Roteia só é usada pelo Roteia. Provedor sem credencial é **pulado**. |
| **P0** — falha de persistência voltava como sucesso | ⭐ A falha **sobe**. E a execução só é considerada registrada se a tabela existir e o filtro de colunas não esvaziar o registro. |
| **P1** — JSON em coluna `json` pelo Query Builder (que não tem casts do Eloquent) | ⭐ `json_encode` explícito. |
| **P1** — URL duplicando `/v1` | ⭐ A base que já vem com `/v1` não recebe `/v1` de novo. |
| **P1** — substituição automática de modelo | ⭐ O modelo pedido é usado, ou o provedor é **pulado**. Trocar o modelo é decisão do gestor, não do código. |
| **P0** — Avello ausente do fluxo inicial | ⭐ `AvelloExecutionAdapter`, com o contrato do PR #172: transporte injetado, desligado por padrão, e **fail closed**. |

⭐ E o mais importante de todos, que a auditoria деяfrontou: ⭐ **o fallback muda
QUAL IA produz, nunca QUEM julga.** A entrega continua indo para
`awaiting_via_review` pelo `MarketingTaskViaGate`. E o adaptador do Avello exige
`evidence_ref` — ⭐ **entrega sem evidência não existe**, porque a VIA audita a
evidência.

## ⭐⭐ O ponto que importa

O executor **não tem acesso à porta de decisão da VIA**. Ele produz texto e
devolve. Não muda status, não registra decisão, não publica.

Quem entrega para a supervisão continua sendo o `MarketingTaskViaGate`, e a
entrega vai para `awaiting_via_review` como sempre. Ou seja: **nenhuma IA
consegue se autoaprovar, por nenhum caminho.**

Isso não é uma convenção — é estrutural. O executor não recebe a porta que
grava a decisão, então mesmo que alguém alterasse o código dele, ele não teria
como registrar uma aprovação. E há testes que verificam isso pela assinatura
das três classes — o executor, o provedor roteado e o adaptador do Avello.

As regras da VIA também vão para o modelo, em texto: o agente produz o trabalho,
mas não aprova, não publica e não passa tarefa para outro.

## ⭐⭐ Onde o fluxo e o custo são medidos

Cada execução é registrada em **`ai_executions`** — a MESMA tabela que o Centro IA
usa. ⭐ Não criar uma segunda tabela de execuções: duas medições de custo e
latência divergem, e o dashboard passaria a mostrar um número diferente do real.

O registro leva o que interessa para avaliar fluxo: tokens de entrada e saída,
latência, modelo realmente usado, e o metadado com agente, departamento e
tarefa.

⭐ **`estimated_cost` fica em 0** — como em todo o resto da plataforma — porque
ainda não há tarifário de provedor no produto. ⭐ Preencher com um palpite seria
pior que zero: um número inventado no dashboard de custo é mais perigoso que um
zero. ⭐ Quando o tarifário existir, ele é preenchido aqui e em lugar nenhum mais.

## ⭐⭐ O que é a CAPABILITY do agente

Cada agente declara uma capability, e é ela que decide **qual provedor atende**:

| Agente | Capability |
|---|---|
| Marketing Director, Product & Market Strategist, Campaign Planner | `marketing_strategy` |
| Copy & Content, Creative Director, Video Producer, Social & Distribution | `copy` |
| QA & Brand Guardian, Performance Analyst | `critical_review` |

⭐ São as mesmas capabilities que o `AiRoutingService` já conhece — a ordem de
preferência por capability é a do Centro IA, e não uma nova.

⭐ E o modelo pedido é uma **preferência**, não uma exigência: se o agente pede um
modelo que o provedor não tem, o roteamento escolhe o que tem — porque perder o
trabalho é pior que usar outro modelo. O modelo realmente usado volta na
resposta, para o registro mostrar a verdade.

## ⭐⭐ Não há uma segunda convenção de variáveis

A plataforma **já tem** uma camada de IA de produção:

- `AiProvider` (tabela) — os provedores, com slug, status e capacidades;
- `AiRoutingService` — escolhe o provedor por capacidade, com fallback
  (roteia → Claude Opus 5 → openai);
- `AiExecutionService` — a chamada real;
- `AiProviderSeeder` — Roteia, Claude Opus 5, OpenAI e HeyGen já semeados.

E as variáveis `ROTEIA_BASE_URL`, `ROTEIA_API_KEY` e `ROTEIA_CHAT_MODEL` já
existem, usadas pelo IA Center.

⭐ **Então o Escritório LÊ AS MESMAS VARIÁVEIS.** Se o Escritório tivesse as
suas próprias (`IA_URL_BASE`, `IA_CHAVE`), teríamos DUAS chaves para o mesmo
provedor — e o dia que uma acabasse e a outra não, o produto quebraria de um
jeito difícil de diagnosticar.

O que é novo são só duas variáveis, e são as que ainda não existiam:

```
ESCRITORIO_IA_MODELOS=deepseek/deepseek-v4-flash,grok-4
ESCRITORIO_MODEL_PADRAO=deepseek/deepseek-v4-flash
```

### Ordem de resolução do modelo

1. o modelo que o **agente** declara (`marketing_agents.agents.<id>.model`);
2. `ESCRITORIO_MODEL_PADRAO` — o padrão do Escritório;
3. `ROTEIA_CHAT_MODEL` — o padrão do gateway (último recurso).

O agente vence porque é o mais específico. O padrão do gateway é o último
recurso porque assim o Escritório funciona sem configuração própria.

## ⭐⭐ Estado atual: desligado, de propósito

**O provedor só é registrado com a configuração COMPLETA** — base, chave **e**
lista de modelos. Sem a lista, não há provedor, o executor falha com mensagem
clara, e o Escritório continua funcionando com a entrega digitada à mão.

⭐ E É POR ISSO QUE A LISTA DE MODELOS É O QUE SEGURA A CHAVE: sem ela, nenhuma
chamada de IA é feita — mesmo com a chave no ambiente.

Este é o estado correto enquanto se **avalia fluxo e custo**: o Escritório roda,
a supervisão da VIA roda, os testes rodam, e nenhuma chamada paga acontece.

Há um teste que garante isso (`test_provedor_so_e_registrado_com_a_configuracao_completa`).

## ⭐ O custo não é medido aqui — de propósito

O `AgentModelResult` devolve tokens, mas `costCents` fica zerado. ⭐ A tabela
`ai_executions` (do IA Center) é onde o custo é medido, e ela é alimentada pelo
`AiExecutionService`. Preencher custo aqui criaria uma **segunda medição** — e
duas medições de custo sempre divergem.

Para avaliar custo, o caminho é usar o `ai_executions` que já existe.

## Como ligar quando for a hora

```
ROTEIA_BASE_URL=https://api.roteia.ai
ROTEIA_API_KEY=<chave>
ROTEIA_CHAT_MODEL=deepseek/deepseek-v4-flash
ESCRITORIO_IA_MODELOS=deepseek/deepseek-v4-flash,grok-4
ESCRITORIO_MODEL_PADRAO=deepseek/deepseek-v4-flash
```

O `ESCRITORIO_IA_MODELOS` é uma lista explícita, e não "deixa ele tentar": o
Escritório recusa um agente cujo modelo não está na lista, com mensagem clara —
em vez de mandar a requisição e receber um 404 difícil de entender.

## Testes

```
composer install
vendor/bin/phpunit tests/Unit/Marketing/AgentTaskExecutorTest.php
```

O teste usa um provedor falso: nenhuma chamada de IA acontece, e o teste roda em
milissegundos. É o mesmo mecanismo de porta que o resto do projeto usa.

## Critérios

1. O executor devolve o texto produzido pelo modelo.
2. O papel do agente e as regras do Escritório vão para o modelo.
3. O parecer anterior da VIA vai para o modelo, para a correção ser dirigida.
4. O executor recusa modelo fora da lista do provedor.
5. O executor recusa tarefa sem agente responsável.
6. O executor falha com mensagem clara quando não há provedor configurado.
7. O executor não tem nenhuma dependência de escrita na decisão da VIA.
8. ⭐ O provedor só existe com base, chave e lista de modelos completas.
9. ⭐ Sem configuração própria, o modelo do gateway é o último recurso.

## Pendências

⏸️ **Avaliação de fluxo e custo antes de ligar as IAs.** Nada aqui foi ligado.

- Nenhuma chamada real contra um provedor foi feita.
- Preço por modelo não é calculado (ver acima: é medido no `ai_executions`).
- Não há fila nem retry: a execução é síncrona.
- O `AiRoutingService` com fallback por capacidade não foi integrado ao
  executor — hoje o provedor é único. Integrar é o próximo passo natural.
- Nenhuma migration foi aplicada; sem merge/deploy/produção.

# Escritório Virtual — Executor de IA dos agentes

## Entrega

O motor que faz os agentes **trabalharem de verdade**. Antes desta entrega, o
Escritório tinha a regra da VIA, os agentes, a sala operacional e a trilha de
auditoria — mas ninguém produzia: a entrega era digitada à mão.

- `AgentModelProvider` — o contrato do que um provedor de modelo precisa saber
  fazer (`name()`, `supports()`, `execute()`).
- `OpenAiCompatibleModelProvider` — adaptador para APIs no formato OpenAI.
  RoteirO, Invision, OpenRouter, Together, Groq e Ollama (com compatibilidade)
  falam esse formato, então **um** adaptador atende todos: trocar de provedor é
  trocar a URL base, a chave e a lista de modelos.
- `AgentTaskExecutor` — junta o registro de agentes (de onde vem o modelo) com o
  provedor, e monta o contexto do Escritório para o modelo.
- `AgentExecutionServiceProvider` — registra o executor no container. O teste
  sobrescreve o binding e passa um provedor falso.
- Configuração por ambiente: `IA_PROVEDOR`, `IA_URL_BASE`, `IA_CHAVE`,
  `IA_MODELOS`, `IA_MODEL_PADRAO`, `IA_TIMEOUT_SEGUNDOS`.

## ⭐⭐ O ponto que importa

O executor **não tem acesso à porta de decisão da VIA**. Ele produz texto e
devolve. Não muda status, não registra decisão, não publica.

Quem entrega para a supervisão continua sendo o `MarketingTaskViaGate`, e a
entrega vai para `awaiting_via_review` como sempre. Ou seja: **nenhuma IA
consegue se autoaprovar, por nenhum caminho.**

Isso não é uma convenção — é estrutural. O executor não recebe a porta que
grava a decisão, então mesmo que alguém alterasse o código dele, ele não teria
como registrar uma aprovação. E há um teste que verifica isso pela própria
assinatura da classe (`test_executor_nao_tem_acesso_a_porta_de_decisao_da_via`).

As regras da VIA também vão para o modelo, em texto: o agente produz o trabalho,
mas não aprova, não publica e não passa tarefa para outro.

## Como ligar um provedor

```
IA_PROVEDOR=roteiro
IA_URL_BASE=https://api.roteiro.ai/v1
IA_CHAVE=...
IA_MODELOS=grok-4,grok-4-fast
IA_MODEL_PADRAO=grok-4
```

O `IA_MODELOS` é uma lista explícita, e não "deixa ele tentar": o Escritório
recusa um agente cujo modelo não está na lista, com mensagem clara — em vez de
mandar a requisição e receber um 404 difícil de entender.

Cada agente pode declarar o seu próprio modelo no registro
(`marketing_agents.agents.<id>.model`). Sem ele, vale o `IA_MODEL_PADRAO` — assim
dá para rodar a fábrica com um modelo só e ir ajustando agente por agente.

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

## Pendências

Nenhuma chamada real contra um provedor foi feita (aguarda credenciais).
Preço por modelo não é calculado — o provedor devolve tokens e a precificação
fica por conta. Não há fila nem retry: a execução é síncrona. Nenhuma migration
foi aplicada; sem merge/deploy/produção.

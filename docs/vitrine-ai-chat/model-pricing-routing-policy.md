# Precos e roteamento de valor — Centro IA

Estado: **fonte de precos inicial cadastrada em configuracao para consulta e comparacao, nao habilitada para chamadas automaticas**.

O arquivo `config/ai_chat_prices.php` armazena uma amostra com IDs completos e precos anunciados no HTML enviado do Invision (captura de 24/10/2026). Valores por 1 milhao de tokens em USD, com taxas separadas para entrada e saida. `verified=false` deliberadamente: anuncios nao comprovam saldo, precificacao contratual nem disponibilidade de API. O catalogo Roteia deve ser consultado em sua propria moeda e nenhum preco nao verificado deve autorizar uma chamada.

## Dados para tabela persistente futura
Campos obrigatorios: provedor, ID exato de modelo, plano, moeda, unidade, entrada, saida, desconto, limite de contexto, capacidades suportadas, modalidade, origem, hora da coleta, expiracao, verificacao do contrato, latencia observada, taxa de falha e qualidade por categoria de tarefa. Auditoria deve armazenar a versao do preco usada na decisao.

## Selecao — motor inicial
`App\\Services\\Ai\\Chat\\ModelValueRouter` compara candidatos com mesmo codigo de moeda, capacidade exigida, orcamento, nota minima e cotacao verificada e nao expirada. O custo previsto eh `(input_tokens * input_per_million + output_tokens * output_per_million)/1000000`. Escolhe menor custo dentre candidatos que passaram pelo filtro; desempata por qualidade. Nao consulta provedores nem executa cobrancas.

## Requisitos para ligar ao runtime
- Persistencia, coleta autorizada e controles de concorrencia de gasto, limites diarios e por projeto;
- Verificacao live de precos Roteia e Invision, saldo e modelo suportado; sem coleta de segredos em texto;
- Conversao BRL/USD com cotacao referenciada por data, sem mistura de unidades;
- Medicao e benchmark de qualidade por tarefa antes de permitir selecao automatica em producao;
- Fallback explicitamente autorizado, cotas e deduplicacao idempotente de chamadas;
- Testes CI e homologacao de ponta a ponta antes de deploy.

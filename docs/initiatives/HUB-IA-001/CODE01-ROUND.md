# HUB-IA-001 — Rodada CODE01 (preparada, não executada)
Data: 2026-11-15
Protocolo: Vitrine API Benchmark v1.0 / CODE01
Ambiente-alvo: Codex pessoal isolado, provider Invision, modelo mostrado Qwen3.8 Max.
Valor máximo autorizado pelo usuário: R$ 2,00 (total da rodada; não é saldo debitado).
Estado: BLOQUEADO PARA INFERÊNCIA PAGA por ausência de controle técnico comprovado de orçamento máximo pré-chamada.

## Prompt literal, congelado
Escreva uma função Python dedupe(items) que remova duplicatas preservando a ordem de uma lista de inteiros. Retorne apenas código.

## Critérios congelados
- [] -> []
- [2,1,2,3,1] -> [2,1,3]
- [-1,0,-1] -> [-1,0]
- Resposta apenas código, sem imports ou efeitos externos desnecessários.
- Registrar request_id, modelo solicitado e upstream (quando disponibilizado), consumo, taxa efetiva, custo debitado, tempo total e status.
- Avaliar saída com Python isolado, sem acesso à rede ou sistema do usuário.
- Resultado aprovado só após teste objetivo e evidência; resposta HTTP 200 não basta.

## Requisitos para destravar a chamada
1. Identificar tarifa efetiva por modelo/conta, unidade e moeda.
2. Demonstrar controle server-side que reserva orçamento máximo <= R$ 2,00 e impede extrapolar saldo da rodada, inclusive tentativas/custos de prompt inicial.
3. Fixar máximo de tokens/saída e concorrência 1; limitar retries e executar uma única sonda inicial.
4. Verificar consumo/cobrança pós-chamada sem expor credenciais.
5. Se qualquer condição faltar, não enviar chamada paga.

## Situação de verificações em 2026-11-15
- O Codex já retornou OK em sessão Invision com 25.7K tokens totais, mas não foi fornecido custo efetivo e upstream.
- Ferramentas descobertas neste chat: Avello2.ask, Avello2.smoke e V5 project_roteia_via_code_pilot_execute; nenhuma delas expôs reserva ou limite monetário máximo por chamada ou por rodada no contrato da ferramenta.
- Não há ferramenta validada de faturamento/cota da conta Invision disponível nesta conversa.
- Logo: 0 chamadas adicionais enviadas, 0 consumo novo intencional nesta rodada. Débito real não foi consultado.

## Ponto de continuidade
Revisar capacidade de controle financeiro no gateway antes de reabrir esta rodada. Não lançar novo PR para contornar o gate e não alterar o config.toml do Codex.

# Classificação dos Models de IA — 2026-07-15

## Decisão

Os Models abaixo pertencem à camada `Shared`, pois representam infraestrutura transversal de IA e não dependem de um produto específico:

- `AiAgent`
- `AiProvider`
- `AiExecution`
- `AiConsumption`
- `AiAlert`
- `AiMemory`
- `AiQueue`

## Evidências

- `AiAgent` coordena provider, filas, execuções e consumo.
- `AiProvider` centraliza provedores e protege `api_key` no serialization.
- `AiExecution`, `AiConsumption` e `AiQueue` relacionam-se com `Company`, `Product` e `License`.
- `AiAlert` registra falhas por empresa, agente e provedor.
- `AiMemory` mantém memória reutilizável por empresa e produto.

## Decisão de movimentação

Os namespaces não serão alterados neste lote. A movimentação física ocorrerá somente após mapear todos os imports em Filament, Services e Jobs, preservando wrappers de compatibilidade.

## Risco

Baixo para classificação; médio para movimentação física devido ao número de relacionamentos e possíveis imports em Resources Filament.

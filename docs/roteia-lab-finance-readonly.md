# Roteia AI Lab no Cockpit — contrato somente leitura

## Escopo
O Cockpit exibe `/admin/roteia-lab-financeiro` no grupo `05 · Financeiro`. O banco, credenciais da Roteia, chamadas pagas, aprovacoes e conciliacao financeira pertencem **exclusivamente** ao laboratorio independente.

O estado padrao e **nao conectado**. Nenhum valor ficticio aparece como saldo real.

## Configuracao de homologacao
Variaveis exclusivas do Core HML, armazenadas fora do repositório:
- `ROTEIA_LAB_FINANCE_BASE_URL`: URL HTTPS base do endpoint financeiro independente; sem sufixo de rota
- `ROTEIA_LAB_FINANCE_ALLOWED_HOST`: hostname exato do laboratorio, sem porta
- `ROTEIA_LAB_FINANCE_READ_TOKEN`: token de consulta com permissoes restritas **finance:read**

Nao se deve reutilizar a chave de API da Roteia. A URL e o token so devem ser configurados depois de existir servico homologado e autenticado. Antes disso, o painel retorna `not_configured`.

## Contrato esperado (ainda nao integrado ao runtime)
- `GET /finance/summary` -> objeto contendo `saldo_disponivel`, `consumo_conciliado`, `reservas_pendentes` (BRL ou null)
- `GET /finance/projects?limit=20` -> `{ "data": [{ "id": "...", "name": "...", "status": "...", "spent_brl": null }] }`
- `GET /finance/transactions?limit=20` -> `{ "data": [{ "request_id": null, "project_id": "...", "status": "...", "reconciled_brl": null }] }`

A semantica financeira deve ser harmonizada com o Budget Planner e Credit Ledger antes de ligar dados reais. Valor desconhecido deve ser `null`, nunca `0`; saldo, consumo e reserva nao podem ser somados como se fossem a mesma unidade financeira.

## Seguranca e limitacoes
- Endpoint HTTPS e hostname explicito, sem IP literal e sem redirecionamentos.
- Cliente HTTP somente GET, bearer de leitura, timeout curto, resposta de erro degradada.
- A API do laboratorio precisa autenticar a chamada, implementar scopes, limitar respostas, auditar acesso e oferecer dados paginados.
- A allowlist de hostname nao substitui protecao contra DNS rebinding ou egress SSRF na infraestrutura; exigir bloqueio de destinos privados na saida de rede.
- O Core nao grava nem modifica registros da Roteia; integra exclusivamente a leitura no Cockpit.
- Nenhuma alteracao de producao, migracao, segredo ou deploy faz parte deste PR.

## Validacao antes de merge
1. Rodar `php -l` nos arquivos PHP novos e alterados.
2. Rodar `php artisan test --filter=RoteiaLabFinanceReaderTest` no checkout isolado da branch.
3. Confirmar via login do Cockpit HML a pagina e ausencia de saldos quando desconectado.
4. Simular respostas autenticadas `200`, `401`, `503`, timeout e redirect para garantir estado degradado.
5. Somente apos servico remoto independente e seguranca aprovados, autorizar integracao real.

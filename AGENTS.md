# Assessor Online — execução supervisionada

## Escopo
Este repositório contém vários produtos da Vitrine IA Pro. O trabalho autônomo descrito aqui limita-se ao Assessor Online, especialmente aos diretórios `app/Assessor`, `app/Http/Controllers/Assessor*`, `routes/assessor*`, `resources/views/assessor`, migrations e testes associados. Não alterar outros produtos sem autorização explícita.

## Fonte de verdade
GitHub é a fonte de verdade. Usar a branch `feature/assessor-cabinet-workflow-20261024` e o PR #124 como ponto de continuidade. Inspecionar alterações preexistentes e preservar trabalho em andamento. Não sobrescrever arquivos ou reconciliar árvores sujas sem preservação.

## Fluxo
1. Ler este arquivo e `docs/OPERACAO-CHATGPT.md` e `docs/HANDOFF.md`.
2. Auditar contratos, autorização, isolamento por gabinete, LGPD e regressões.
3. Implementar pequenas mudanças, com testes e mocks quando necessários.
4. Registrar comandos, resultados, commits, riscos e próximos passos no HANDOFF.
5. Preparar PR; nunca fazer merge ou deploy por inferência.

## Autorizações
CONTINUE = leitura. EXECUTAR = alteração de código e testes no escopo imediato. Exigem autorização separada: merge, produção, migrations aplicadas, custos de API, secrets, alterações de infraestrutura e ações destrutivas.

## Qualidade
Não confundir commit com teste aprovado. Verificar sintaxe, rotas, CSRF, permissões, isolamento multi-gabinete, auditoria transacional e experiência de uso. Não divulgar dados pessoais de cidadãos nem segredos. Nenhuma IA externa sem autorização e política de dados.

# Escritório Virtual DEV/ECO — MVP de missões

## Entrega
- Registro persistente de missões DEV/ECO em office_missions e office_mission_events.
- Criação e primeiro evento em transação; idempotência UUID.
- Histórico com eventos e ator.
- Acesso restrito a usuário autenticado, ativo e admin; histórico limitado ao criador.
- Rotas de leitura e gravação exclusivas do ambiente homologation.
- Nenhuma chamada n8n, Avello ou VIA nesta entrega.

## Testes
\`composer install\`
\`vendor/bin/phpunit tests/Feature/OfficeMissionTest.php\`

## Critérios
1. Admin registra missão e a consulta após novo GET.
2. Evento mission_created aparece uma vez.
3. Reenvio idempotente não duplica.
4. Cliente recebe 403.
5. Admin não vê missão de outro criador.
6. Nenhuma execução automática é disparada.

## Pendências
CI, revisão de segurança, migration aplicada em HML autorizada, HTTP real, Avello autenticado, workflow n8n, VIA server-side e E2E. Nenhuma migration foi aplicada; sem merge/deploy/produção.

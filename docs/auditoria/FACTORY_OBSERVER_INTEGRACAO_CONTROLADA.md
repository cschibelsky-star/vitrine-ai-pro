# Factory Observer — integração controlada (2026-11-19)

## Estado
- FactoryObserver: allowlist de quatro capacidades, sem rota HTTP.
- FactoryReadOnlyProvider: implementa somente factory.health e factory.project.inspect.
- factory.build.inspect e factory.qa.inspect: **não implementadas**; devem falhar de modo explícito.
- Não há conexão operacional com VIA Agent Hub, autenticação de chamadas, deploy ou leitura de banco comprovada.
- Código isolado na branch de auditoria; não está em produção.

## Integração pretendida
Somente após autenticação, autorização por capability, validação de contexto de missão, registro de auditoria e limitação de taxa, o Tool Gateway poderá instanciar:
```php
$observer = new \App\Factory\Observer\FactoryObserver(
    new \App\Factory\Observer\FactoryReadOnlyProvider()
);
$result = $observer->inspect('factory.project.inspect', ['project_id' => 'slug-validado']);
```
O exemplo acima é código de integração, **não** uma rota autorizada a ser publicada.

## Segurança e pendências
1. Confirmar schema da tabela factory_projects no banco de homologação e aplicar testes de integração em transação read-only.
2. Revisar a compatibilidade de ChecksFactoryPermissions com usuários reais; a exceção de admin explícito exige aprovação.
3. Definir identidade do Agent Hub, escopos, política default-deny, trilha de auditoria e tratamento de dados pessoais.
4. Validar filtros por tenant/empresa antes de expor project.inspect. Atualmente o provider não recebe contexto de tenant; **não habilitar em API**.
5. Reduzir capabilities anunciadas às efetivamente implementadas após estabelecer contratos de discovery.
6. O SmartQa2Service::inspect() existente chama File::ensureDirectoryExists e não é adequado para o modo somente leitura.
7. Executar CI e testes Laravel; os arquivos versionados não comprovam resultados de execução.

## Critério de liberação
Nenhuma exposição por HTTP ou integração de agente antes de passar: autenticação, isolamento de tenant, autorização, auditoria, testes integrados, aprovação de governança e homologação.

# Factory Core — preflight do bootstrap (proposta de PR)

## Mudança de contrato

O comando `php artisan factory:install` passa a ser **somente planejamento** por padrão. Não dispara `migrate`, `factory:sync`, `db:seed` ou `factory:health` nesse modo.

A aplicação real do bootstrap requer todos os controles abaixo:
1. `FACTORY_CORE_BOOTSTRAP_ENABLED=true` configurado **no servidor não produtivo e revisado pelo operador** (padrão `false`);
2. Ambiente Laravel dentro de `local`, `testing`, `hml`, `homologation` ou `staging` — nunca `production`;
3. Opções explícitas `--apply --confirm=FACTORY_CORE_BOOTSTRAP`;
4. Aprovação operacional específica do ambiente e backup validado **antes** de rodar o comando.

Exemplo **documental, não executado**:

```sh
php artisan factory:install
# planejamento apenas

# Somente em ambiente NÃO produtivo previamente autorizado:
php artisan factory:install --apply --confirm=FACTORY_CORE_BOOTSTRAP --no-sync
```

A aplicação migra primeiro; se o comando falhar, para sem executar sync, seed ou health. O comando não é transacional: se migration for parcialmente aplicada ou sync/seed falharem depois, a intervenção humana é necessária.

## Limites

- Este PR não autoriza executar migrations; só exige controles adicionais quando um operador autorizado fizer isso mais tarde.
- Não protege chamadas diretas `php artisan migrate`, seeds, scripts de instalação legados ou outras rotas de alteração de schema. Devem ser governadas por RBAC, permissões de deploy e controle de ambiente.
- `--seed` só tem efeito na aplicação real; `--no-sync` evita sincronização no apply.
- Revisar consumidores que esperavam que `factory:install` sem parâmetros executasse o bootstrap. Não dar merge sem homologação de compatibilidade.
- Não configurar secrets, habilitar o flag ou implantar no VPS neste PR.

## Aceitação

CI de sintaxe + teste de guard deve passar; depois revisar invocações existentes, executar teste controlado em banco descartável e avaliar staging com backup e autorização específica. A VIA permanece observadora sem capacidade de autorizar migrations.

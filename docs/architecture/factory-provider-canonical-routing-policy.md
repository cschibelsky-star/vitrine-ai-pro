# Politica canonica de enderecos e implantacao - Factory / Provider / Orquestrador

Status: especificacao operacional em branch isolada; NAO ativada no runtime.

## Fluxo obrigatorio

Factory -> Provider de implantacao -> Registro canonico -> DNS -> Traefik/HTTPS -> HML -> Gate auditavel no Cockpit -> Producao.

## Regras vinculantes para o executor

1. Antes de criar qualquer endereco, consultar Factory, Provider, registro de projetos, containers existentes, labels Traefik e DNS. Reutilizar a identidade canonica; nao criar rotas duplicadas nem trocar hostname automaticamente.
2. Registrar project_id, deployment_id, ambiente, hostname, dominio base, provedor DNS, upstream, service, rede, volumes persistentes, branch/commit e estado do gate. Divergencias bloqueiam publicacao.
3. Provisionar DNS apenas no provedor autoritativo e com credenciais autorizadas; confirmar IP/target com a infraestrutura efetiva. Nunca inferir IP de outro dominio.
4. Conferir resolucao publica, roteador Traefik, rede/upstream, certificado HTTPS, health HTTP e Admin. Estado 'planned' nao e publicavel; falhas de autenticacao e de allowlist sao bloqueios, nao permissao para contornar controles.
5. Alteracoes devem ser idempotentes, com plano/diff, trilha de auditoria e rollback delimitado. Preservar arvore Git suja, volumes, dados, segredos e versao visual aprovada. Nao executar reset/clean.
6. HML e obrigatoria antes da producao. Promocao somente apos testes, evidencia e aprovacao auditavel no Cockpit. Producao nunca recebe mudancas por simples criacao de rota.
7. Se o Provider ou Factory estiver inacessivel, registrar incidente e interromper publicacao. Nao inventar endpoint, novo DNS ou novo deployment para contornar bloqueio.

## Reconciliacao Conheca Sumare

Projeto: conheca-sumare. Container observado: conheca_sumare_hml_web, em execucao, com labels Traefik para conheca-sumare-hml.vitrineaipro.com.br. O hostname nao resolve publicamente; o acesso ao provedor DNS falhou por autenticacao. Registro alternativo p000008-hml esta 'planned', nao ativo, e nao deve substituir automaticamente a rota existente.

Passos: recuperar autenticacao Provider DNS; confirmar autoridade da zona e IP publico real do Traefik; reconciliar o hostname original no registro canonico; validar DNS/HTTPS/HTTP/Admin; somente entao retomar correcoes da Home na HML.

## Criterios de aceite para futura implementacao

- Testes: rota existente reutilizada; chamada repetida nao cria duplicata; hostname com DNS ausente bloqueia; Provider sem credenciais bloqueia; status planned bloqueia; servico nao autorizado bloqueia; producao sem gate bloqueia.
- Evidencias de DNS, TLS, proxy, health e aprovacao anexadas ao deployment.
- Integracao no executor real de provisionamento e no Cockpit antes de marcar esta politica como aplicada.

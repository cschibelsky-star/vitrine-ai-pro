# Integração de publicação — 09/10/2026

## Escopo e estado

Código em revisão, sem instalação, merge, alteração de DNS, migrations ou deploy.
O gate é conectado ao workflow real `.github/workflows/deploy-hostgator.yml`
e à atualização de repositórios pelo Project Manager de `Vitrine-IA-Pro-VPS`.
Isso **não comprova cobertura dos executores externos Super/V5/V4, SSH manual
ou compose-service**: seu código executado não foi reconciliado com este checkout.
A ativação global continua bloqueada até testar essas entradas e remover bypasses.

## Evidência operacional do piloto

- V5 `project_context`: `conheca-sumare-independent` e `conheca-sumare-prod`
  apontam para `cschibelsky-star/visite-sumare`, não `vitrine-ai-pro`.
- HML: `p000002.hml.vitrineiapro.com.br`; produção: `www.conhecasumare.com.br`.
- Produção reportou alterações locais em nginx.conf, sw.js, Home.jsx, api.js
  e docker-compose.prod.yml. Não foram alteradas ou descartadas.
- Super reportou zero bindings para `conheca-sumare-prod`.
- A origem do PHP aprovado, a imagem executada e a rota externa precisam ser
  reconciliadas sem sobrescrever essas árvores. O cadastro aponta à versão
  candidata PHP em `vitrine-ai-pro`; a divergência bloqueia a aprovação.

## Fluxo obrigatório

1. Coletor operacional mede checkout servido, SHA da imagem, árvore limpa,
   mounts de dados/segredos, redes, integridade do backup e script de rollback.
   Assina bytes com Ed25519. Não aceita indicadores booleanos de uma requisição.
2. Core valida assinatura, projeto, URLs, repositório, executor e prazo de 15 min.
   Consulta o head e os workflows obrigatórios no GitHub e DNS/HTTPS diretamente.
   O workflow mais recente para cada caminho deve ter concluído com sucesso
   no SHA exato e no mesmo repositório; ausência ou paginação incompleta bloqueia.
3. Administrador ativo de role explícito `admin` aprova o SHA pelo Cockpit, com
   sessão e CSRF. Usuários legados sem role não ganham autorização de publicação.
4. Executor autenticado consome aprovação uma única vez, sob lock por projeto,
   após nova coleta. Publica somente o SHA autorizado. Falha consome a aprovação:
   é necessária nova revisão antes da repetição.
5. Mudança de head/SHA testado, prazo vencido ou perda do papel administrativo
   invalida a aprovação. Webhook push com HMAC invalida também após avanço/reversão
   do ref. Sem webhook, só é garantida a invalidação ao consultar/consumir, não
   a observação de mudanças transitórias entre consultas.

## Dependências para ativação em HML

- Diretórios privados persistentes, fora do checkout, para ledger e evidências.
  `PUBLICATION_EVIDENCE_DIRECTORY` e `PUBLICATION_LEDGER_DIRECTORY`.
- Chave pública base64 `PUBLICATION_COLLECTOR_PUBLIC_KEY`; chave privada apenas
  no coletor operacional. Não materializar a chave em conversa ou no Git.
- `PUBLICATION_GITHUB_TOKEN` somente leitura de commits/Actions;
  `PUBLICATION_EXECUTOR_TOKEN` exclusivo por implantação do controle;
  `PUBLICATION_GITHUB_WEBHOOK_SECRET` e webhook push configurado no GitHub.
- Coletor Python com cryptography; config protegida fora do repo, propriedade de
  operador separado do usuário da aplicação. A config lista containers, checkout
  e destinos dos mounts, arquivo de backup e script de rollback. Não inserir
  credenciais. Ausência de labels de revisão, mounts ou redes comprovadas bloqueia.
- HML e PROD não podem compartilhar redes até existir medição de política de
  isolamento. A separação de volumes sozinha não prova isolamento de dados.
- Workflows QA completos do piloto devem rodar no SHA exato; apenas calendário
  não cobre mídia, navegação, PWA, formulários ou acesso. Adicionar esses workflows
  à lista obrigatória antes de liberar o piloto.
- Configurar URL HTTPS do controle e token no executor, environment `production`
  protegido e regras que impeçam executar workflows de deploy alterados em refs
  não confiáveis. Não considerar o environment GitHub substituto da aprovação.
- Project Manager exige environment explícito: desconhecido bloqueia atualização.
  Produção exige target_sha e recusa árvore suja. Instalar apenas após revisão.

## Auditoria e limites

Ledger tem escrita atômica, lock e eventos com cadeia de hashes. Não é um storage
WORM: um administrador de filesystem ainda pode reescrevê-lo. Arquivamento em
coletor append-only externo é necessário para auditoria resistente a esse atacante.
A autorização consumida registra uma tentativa autorizada, não uma publicação
concluída. O retorno de deploy deve ser reconciliado com SHA/imagem/health reais;
nenhuma tela marca automaticamente `Publicado` só porque houve aprovação.
Rollback é um plano ligado ao backup verificado e ao hash do script existente;
não equivale a ensaio de restauração. Esse ensaio continua requisito operacional.

## Testes

- `php tests/publication_gate.php`: negação sem aprovação, flags insuficientes,
  cliente, mudança de SHA, reuso, revogação, expiração, indisponibilidade e assinatura.
- PHPUnit `tests/Feature/PublicationControlTest.php`: fronteira HTTP, autenticação,
  cliente, admin sem evidência, flags forjadas e webhook adulterado.
- VPS: `python3 -m unittest discover -s project-manager -v` cobre ausência de SHA,
  ambiente desconhecido, produção disfarçada de HML, árvore suja e zero comandos
  mutáveis quando o Cockpit nega a publicação.
- Nenhum teste aciona publicação real.

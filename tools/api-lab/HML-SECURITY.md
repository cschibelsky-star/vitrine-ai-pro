# API Lab — homologação de segurança, 27/10/2026

Estado: validação local isolada concluída; homologação remota e integração bloqueadas até os gates abaixo. Base revisada: PR #128, commit 02ca291628193171e376a8e46dfdc71f2167b77d. Sem merge, deploy ou chamadas pagas nesta rodada.

## Evidência reproduzível

Python 3.11+, biblioteca padrão: `python -m unittest discover -s tests -v` a partir de tools/api-lab. A suíte original passou com 29 testes. A ampliação reproduziu quatro falhas antes da correção: finalidade arbitrária com assinatura válida, campo adicional ignorado na chamada interna, confirmação de preço textual em vez de booleana e ausência de ocultação do token HTTP em artefatos. A primeira ampliação contém 46 testes distintos, sem herança duplicando casos. Usa transporte simulado, chaves sintéticas, SQLite temporário e HTTP real somente em loopback. Nenhuma evidência corresponde a inferência ou cobrança real de fornecedor.

Correções: validação central dos argumentos também na API interna; finalidade limitada a test/exception; confirmação de teto estritamente booleana e fonte textual não vazia; ocultação do token HTTP e segredo de aprovação em respostas de fornecedor, além do conteúdo dos artefatos; validade máxima de grant reduzida de 15 para 5 minutos para corresponder ao emissor e à documentação; valores monetários acima da capacidade inteira do SQLite rejeitados antes da gravação. O teste antigo de ausência de autorização agora usa purpose=exception; finalidades desconhecidas têm testes de rejeição próprios.

## Matriz de aceitação

| Controle | Evidência local | Limite da conclusão |
|---|---|---|
| Reserva por execução e global | Rejeição antes do transporte, inclusive duas conexões concorrentes | Reserva depende de teto conservador certificado; não impõe teto de cobrança ao fornecedor |
| Persistência de orçamento | Execuções expiradas continuam no total | Banco deve ser durável; substituir/apagar o banco contorna a contabilidade administrativa |
| Idempotência | Mesmo resultado após reinício; conflito rejeitado; timeout não reenvia | Não oferece retomada automática de tentativa em voo; não compartilhar conexão sem serialização |
| Exceção específica | Ação/hash de todos os argumentos, prazo, assinatura e nonce | HMAC exige segredo no servidor verificador; isolamento do processo é requisito de implantação |
| Uso único | Replay negado, inclusive após reinício | Token consumido antes da execução; falha posterior exige nova aprovação externa |
| Sem autoautorização MCP | Inventário fechado, sem approve/grant/shell/deploy/proxy | Não comprova isolamento do host nem aprovação humana real |
| Namespace | Artefatos de uma execução ausentes em outra | Single operator: run_id não é ACL por usuário |
| Ambiente e destinos | Sem parâmetro de destino/ambiente arbitrário; argumento extra rejeitado; hosts fixos; DNS privado rejeitado | Não é classificador semântico de prompts; destinatários e dados reais exigem governança externa |
| HTTP | Bearer, Host/Origin, loopback obrigatório, lock e protocolo | Não comprova proxy TLS, OAuth ou compatibilidade com ChatGPT |

Exceção não amplia orçamento, destino, ferramentas nem duração da execução. A capacidade de assinar não deve ser concedida ao agente. A autorização humana não pode ser inferida de um authorization_ref escrito pelo próprio agente. O piloto atual verifica assinatura, não a existência de consentimento no Cockpit. Não homologar esse fluxo como completo.

## Plano isolado de HML

1. Criar serviço/usuário exclusivo, diretório privado fora do checkout (0700), banco e configuração privados (0600), namespace e porta de HML próprios. Não montar diretórios, Docker socket, credenciais ou bancos de produção. Não apagar/trocar banco para renovar orçamento. Não provisionado nesta rodada.
2. Primeira fase sem credenciais de fornecedores, catálogo/modelos vazios e orçamento administrativo zero. Rodar a suíte e o handshake stdio; validar listener em 127.0.0.1. Preservar evidências sanitizadas e identificação do commit. O transporte de teste é injetado somente pela suíte, sem endpoint público para alterná-lo.
3. Separar emissor externo do processo cliente/agente. Não montar approve.py, segredo de assinatura nem shell do host no ambiente do agente. Na implantação atual o verificador HMAC também possui o segredo: considerar verificação assimétrica ou serviço externo antes da exposição remota. Vincular consentimento autenticado à identidade, operação exata, run_id, destino/modelo/hash, orçamento, ambiente HML, prazo e referência imutável de auditoria. Falha ou indisponibilidade deve negar a exceção.
4. Validar reinício, cópia de segurança/restore do banco sem rollback de reservas/nonces, isolamento de processos e duas tentativas concorrentes no host HML. Executar cada instalação com um único operador; não anunciar multiusuário.
5. Somente após autorização específica de orçamento e tarifas verificadas, preparar eventual sonda paga separada. Nenhum valor de configuração autoriza gasto por si só. Registrar request_id, reserva, custo reportado e débito conciliado separadamente.

## Gates para orquestrador e ChatGPT

- Continuar draft e test_only; sem tráfego de clientes, fallback operacional, produção ou autorização permanente.
- Identidade/OAuth compatível com o cliente, isolamento e permissões por usuário/execução, TLS/proxy, limitação global de requisições e ensaio de autenticação remoto. Não alegar suporte remoto com base no loopback.
- Aprovação humana no Cockpit com trilha verificável, emissão externa específica e uso único; ensaio negativo de autoemissão e replay entre instalações. A audiência instance_id está implementada e testada; garantir identidade única por instalação e evitar reutilização do mesmo segredo em bancos distintos na implantação.
- Aprovação de orçamento fornecida fora do agente e vinculada à execução. run_create agora exige grant externo assinado para orçamento positivo, vinculado ao valor e prazo exatos, de uso único e específico da instalação. Ainda falta verificar consentimento humano no Cockpit.
- Ledger durável, conciliação de custo real, política para falhas de cobrança e auditoria de grants/negativas. Nonce consumido no SQLite não substitui trilha de consentimento do Cockpit.
- Registrar no orquestrador a allowlist exata das seis ferramentas; negar operações desconhecidas e qualquer uso como conector operacional genérico. Testar que nenhuma seleção automática concede exceção.
- Revisão independente dos gates antes de habilitar credenciais, endpoint remoto ou integração. Voz/imagem/vídeo continuam não implementados.

## Continuação — autorização de orçamento e audiência

Base desta continuação: ce342a70c438839b5f45033ab9514be730f39818. Os checks API Lab isolated tests (40546969125) e Architecture Inventory (40546969108) estavam concluídos com sucesso nessa base.

Nova suíte: 58 testes distintos passaram via `python offline_hml.py`. Inclui negação de orçamento positivo sem grant, tentativa do próprio cliente MCP de autoaprovar, alteração de orçamento/prazo, troca de finalidade, grant em outra instalação, replay após reinício, consumidores concorrentes e emissão por CLI externo com segredo sintético. O teste do emissor captura o token sintético internamente, sem publicá-lo. O executor de HML bloqueia sockets Python externos e remove credenciais conhecidas do seu processo; não é sandbox de sistema operacional e não instala serviço na VPS.

O servidor passou a exigir grant assinado para todo run_create com orçamento positivo. O grant assina exatamente purpose, budget_brl e ttl_seconds e tem audiência instance_id. A autorização de orçamento não serve para uma ação excepcional. Grants excepcionais também exigem a audiência. Sem identidade de instalação ou segredo de verificação, falha fechada. Autorizações antigas sem audiência são rejeitadas. Orçamento zero não exige grant e não permite chat que precise reservar valor positivo.

O consumo dos grants e a auditoria mínima de consumo são gravados na mesma transação SQLite. Guarda nonce, ação, hash dos argumentos, referência externa, instalação, execução e tempos; não guarda token. A criação de uma execução positiva vincula o registro ao run_id criado. Não há endpoint MCP de emissão ou alteração dessa auditoria.

Continuam pendentes: consentimento humano verificado no Cockpit, isolamento comprovado do emissor/servidor/agente, auditoria de negativas e trilha externa imutável, TLS/OAuth, HML remota, rate limit global, conciliação real e integração controlada. O HMAC ainda põe o segredo também no verificador; assinatura não comprova segregação do host. Não há autorização permanente nem promoção automática para uso operacional.

## HML descartável em contêiner — 29/10/2026

Base: 2ed991340ec7064370a68450b84716fcde0da7ef; API Lab e Architecture Inventory concluídos com sucesso nessa base. A próxima camada adiciona Dockerfile.hml-test e o job container-hml. Não é serviço persistente, implantação na VPS ou integração ao Cockpit. O host local não possui Docker; a execução efetiva da imagem e das proteções de SO será verificada nos logs do job do GitHub.

O job constrói uma imagem somente com código de teste permitido pelo .dockerignore, sem configuração privada ou banco. Executa como UID/GID 65532, sem rede externa (--network none), raiz somente leitura, sem capabilities e com no-new-privileges. Limites: 1 CPU, 256MiB de memória, 64 processos, /tmp temporário de 64MiB com noexec/nosuid. Não monta host, socket Docker, credenciais ou dados de produção. O job não solicita secrets nem usa ambientes de deployment. O Docker build precisa obter a imagem-base; somente o contêiner de testes é sem rede externa.

Antes da suíte, container_hml.py verifica usuário não root, CapEff zero, NoNewPrivs ativo, mount raiz ro, interfaces limitadas a lo e ausência de credenciais conhecidas. Se qualquer condição falhar, encerra sem executar a suíte. Sete testes negativos/de admissão verificam as condições; a comprovação real de SO depende do job container-hml, não desses mocks. Total esperado: 65 testes, incluindo os 58 anteriores.

A imagem contém approve.py e fixtures de assinatura sintética para testar o protocolo. Não usar esta imagem como servidor conectado: não comprova segregação entre emissor e verificador reais. O job valida a camada de isolamento da suíte, sem inferência, custo ou autorização humana reais. TLS/OAuth, Cockpit, consentimento autenticado e conciliação continuam pendentes.

## Preparação da suíte na VPS — 29/10/2026

Evidência confirmada do commit eed3818: GitHub Actions 40942737540, job container-hml 123690059369, build e proteções de SO aprovados, 65 testes passaram dentro do contêiner; Architecture Inventory também aprovado.

Inspeção do Super Centro confirmou que vitrine-ai-pro é o Core HML, no workspace /srv/projects/vitrine-ai-pro, com branch e integrações de provedores próprias. Não reutilizar esse checkout/runtime. O identificador proposto vitrine-api-lab-hml não estava registrado na inspeção inicial. Preparar projeto e workspace separados /srv/projects/vitrine-api-lab-hml, sem copiar configuração, tokens, volumes ou bancos do Core.

compose.hml-test.yml executa somente api_lab_hml_test, a suíte descartável já validada no GitHub: network_mode none, usuário 65532, raiz ro, capabilities removidas, no-new-privileges, limites de CPU/memória/processos e /tmp temporário. Não há portas, domínio, env_file, volumes persistentes ou credenciais de fornecedor. O resultado esperado é contêiner encerrado com exit code 0 e log de 65 testes aprovados; não é servidor MCP permanente e não exige restart automático. Não interpretar registro/clonagem como teste executado.

Implantação efetiva, SHA do checkout, build e exit code devem ser confirmados pelos conectores antes de marcar a validação na VPS concluída. Nenhuma rota pública deve ser criada para esta suíte. O futuro serviço MCP HML e sua integração ao Cockpit continuam sujeitos aos gates anteriores.

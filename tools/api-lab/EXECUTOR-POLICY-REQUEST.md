# API Lab — solicitação delimitada de política do executor

Estado em 29/10/2026: proposta revisável; não publicada no código do executor nem instalada. O workspace separado está clonado na VPS, mas build e execução permanecem recusados com controlled_service_not_allowed. Nenhum teste da suíte foi executado na VPS.

O arquivo executor-policy-proposal.json define o escopo exato: projeto vitrine-api-lab-hml, serviço api_lab_hml_test, Compose tools/api-lab/compose.hml-test.yml e commit 0584a1c8955225767ef783957f5a33d30f1dc252. Autorizar somente build e run_once, mais status/logs. Não liberar inicialização permanente, outros serviços, atualização do conector, shell ou chamadas de provedores.

A proposta exige conferir hashes dos 13 arquivos revisados e executar sobre snapshot privado só com os bytes aprovados. Arquivos extras, dotenvs e alterações simultâneas do checkout não devem entrar no build. Não importar credenciais nem .env.runtime. Manter as proteções do perfil offline já comprovado pelo GitHub Actions.

Preparação local: 17 testes de política e 6 de composição de overlay passaram, usando processos Docker simulados e sem fornecedores. Esses resultados não comprovam instalação nem build da imagem protegida do executor. Nenhum código de política foi aceito no repositório da VPS nesta rodada.

Bloqueio de autorização: a revisão automática rejeitou gravar arquivos amplos do executor no repositório Vitrine-IA-Pro-VPS, por incluírem também rotinas de secrets e backups e por considerar insuficiente a autorização para esse escopo entre repositórios. Não contornar essa recusa com alteração indireta ou execução genérica. A proposta declarativa permanece no PR #128, sem executar ou instalar política.

Próxima decisão necessária: autorização explícita para um PR separado no repositório Vitrine-IA-Pro-VPS, limitado à política do serviço API Lab e à sua integração na imagem protegida. Instalação administrativa e teste real na VPS são etapas posteriores, com evidências próprias. O PR #36 é a dependência de composição identificada; não misturar sua ativação com a publicação do laboratório. Tokens, orçamento, Cockpit, TLS/OAuth e produção não são liberados por esta proposta.

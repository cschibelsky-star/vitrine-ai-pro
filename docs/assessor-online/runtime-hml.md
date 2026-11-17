# Runtime experimental HML

Shell isolado (PHP 8.3/SQLite + Nginx), sem contas, migração ou ativação automática. Rede interna e volumes por projeto, chave gerada dentro do volume privado, nenhuma referência a secrets/volumes de produção. Mail array; proxy permite apenas demo, health e rotas de avaliação. Admin, faturamento, IA e publicação não são expostos. Avaliação permanece false.

Super bootstrap criou assessor-online-trial-hml em /srv/projects/assessor-online-trial-hml, branch do PR #109, preservando o checkout assessor-online existente. V5 possui registry distinto: qualquer manifest espelho deve apontar somente para esse workspace e compose.

Runtime não é tenant provisionado nem aplicativo parlamentar completo. Faltam adapter oficial, registro Cockpit, identidade verificada e revogação no proxy. A rota oficial só poderá ser anunciada depois de HTTPS e health válidos.

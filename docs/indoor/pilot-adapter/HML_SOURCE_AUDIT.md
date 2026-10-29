# Auditoria de fontes TV Sumaré — workspace VPS (29/10/2026)

Leitura somente via conector operacional do projeto `tvsumare`. Workspace encontrado: `/srv/tvsumare/repository`; `tvsumare_git_status` informou branch `preservation/tvsumare-20261028T191826Z`. `project_context` ainda referencia `feature/tvsumare-boletim-social`, logo há divergência de referência que impede presumir commit/branch de HML e produção.

## Arquivos presentes no workspace, ausentes na main examinada
- `includes/home_featured.php`: seção de destaque editorial à esquerda e publicidade à direita, implementadas separadamente; utiliza `data/home_destaques.json` e `tvs_premium_ads()`.
- `includes/home_feature_ads.php`: lê `data/anuncios_pedidos.json`, exige `status=publicado`, placement `Home` ou `Home Premium`, período válido, mídia local segura em `uploads/anuncios`; restringe MP4 à política de duração e gera até 40 itens. Não exporta dados pessoais dos pedidos.
- `admin/area-comercial.php`: aprovação prévia, publicação, pausa, alterações de mídia com nova aprovação, validações de MP4 (até 50 segundos e 20 MB na interface).
- `includes/tvs_public_helpers.php`: `tvs_load_real_videos()` consulta `data/videos.json` e `data/videos_ia.json`, filtrando estados incompletos/inativos; não dá acesso autorizado a vídeos inéditos automaticamente.

## Implicações
1. O layout editorial/comercial existe no workspace da VPS, mas **não prova que esteja no container HML implantado** nem que o branch de contexto esteja atualizado. Confirmar digest/versão de contêiner e endpoint HTTP HML isolado antes do acoplamento.
2. O status `publicado` em TV Sumaré **não significa direito de retransmissão Indoor**; requer permissão independente por item e anunciante.
3. Conteúdos próprios ainda não publicados exigem fluxo de aprovação Indoor na biblioteca, não devem entrar pela função pública `tvs_load_real_videos()`.
4. O conector Indoor deve receber projeções mínimas e sem PII; não expor o JSON inteiro de `anuncios_pedidos.json`.
5. Não assumir que `home_featured.php` representa a versão canônica final até reconciliar as branches.
6. A implementação do endpoint de leitura autenticado fica bloqueada até a escolha da fonte real HML e assinatura de autorização/identidade entre plataformas.

## Próxima fatia de código sugerida
Criar uma exportação **privada** do catálogo autorizado na TV Sumaré, com seleção explícita `indoor_consent`; garantir que todos os itens sem autorização explícita produzam lista vazia. Consumidor Indoor aplica segundo gate de revisão `pending_indoor_review`. Implementar testes de consentimento, status, mídia e isolamento sem tocar produção.

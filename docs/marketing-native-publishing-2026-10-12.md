# Marketing IA também agenda e publica — candidato de 12/10/2026

O Marketing IA mantém criação, revisão, aprovação, galeria, calendário e distribuição das próprias campanhas. Social Mídia mantém sua interface e seu fluxo por cliente e usa o mesmo receptor de distribuição por API; Centro Operacional governa acesso/runtime. A separação de produtos não impede publicação dentro do Marketing.

## Auditoria atual

Fonte operacional identificada: projeto vitrine-marketing-agents-core-hml, repositório cschibelsky-star/vitrine-ai-pro, branch feature/marketing-agents-core-v1, HEAD 0ad0e4dd6d262074d83dd44fb2c7029445b02f55. Árvore limpa e sincronizada segundo V5. Não alterar a árvore nem instalar este candidato durante esta etapa.

MarketingDashboard já possui publishPieceNow e planPiecePublication. routes/console.php já possui marketing:publish-due e seu scheduler; corrigindo o diagnóstico inicial, o executor existe no código. A existência não comprova execução operacional. SocialDistributionHandoff e routes/api.php possuem o receptor legado usado pelo Social.

## Alterações

- NativePublicationService é compartilhado pela galeria Marketing, executor existente e bridge Social contrato v2.
- Instagram: Feed JPEG; Stories imagem/vídeo com media_type=STORIES; Reels vídeo com media_type=REELS. Poll único por execução, persistência do container antes de prosseguir, retomada do mesmo container quando FINISHED. Nada de sleep/poll bloqueante longo.
- Facebook: Feed imagem. Stories/Reels Facebook ainda não implementados neste candidato, retornam FORMAT_NOT_AUTHORIZED; nunca cair silenciosamente em Feed ou vídeos genéricos.
- Canal explícito impede que a presença de um Instagram vinculado desvie uma publicação escolhida para Facebook.
- Aprovação inclui canal, formato e legenda, além da versão/mídia. Peças antigas exigem nova aprovação com o novo fingerprint antes de publicar.
- Ledger em storage local persistente com flock e gravação atômica: identidade inclui produto/cliente/peça; mudança de request/chave é conflito. Marca CREATE_SUBMITTED/PUBLISH_SUBMITTED antes do POST. Timeout/crash durante um POST bloqueia repetição (UNKNOWN). PROCESSING só retoma container conhecido; não repete criação.
- A galeria é recarregada sob o mesmo lock usado para edição antes do envio manual; alterações/cancelamento depois de registrar envio são bloqueadas. Scheduler usa o mesmo lock da galeria.
- API status declara capacidades somente se publicador estiver habilitado e capacidades explicitamente configuradas. API publish exige v2/placement/type/mime/hash/chave. publication-status é somente consulta, sem efeito Meta.
- Bridge deixa de aceitar o contrato legado image-only nesta branch candidata. Não instalar antes de coordenar com Social PR #9; esse PR também exige contrato v2 e permanece desligado.
- Containers assíncronos do Social são retomados pelo comando marketing:resume-native-publications, com envelope criptografado e referência da conexão; consulta de status permanece leitura.

## Segurança e configuração pendentes

MARKETING_NATIVE_PUBLISHING_ENABLED=false por padrão. MARKETING_NATIVE_PUBLISHING_CAPABILITIES deve listar somente formatos e contas validados. Exemplo de capacidades implementadas: instagram.feed.image,instagram.stories.image,instagram.stories.video,instagram.reels.video,facebook.feed.image.

Não houve posts reais, geração IA, créditos, migrations, merge ou deploy. A flag e as permissões Meta precisam ser configuradas explicitamente após HML operacional isolada e teste das contas. App Review/elegibilidade Stories/tokens/Graph continuam dependências externas.

A imagem do Marketing precisa ter publication_mime_type=image/jpeg e uma cópia final JPEG revisada. Os PNGs da campanha foram inspecionados na etapa do Social, mas este candidato NÃO converte automaticamente a galeria Marketing: importar/preparar a cópia final antes de nova aprovação. Asset URL deve entregar arquivo imutável HTTPS durante o processamento; a versão aprovada protege campos, mas não comprova imutabilidade de uma URL de terceiros. Validação binária/MP4 do Social não deve ser confundida com validação binária no Marketing.

Storage do ledger precisa sobreviver a releases/restarts e ser compartilhado entre processos que executam publicação. flock desta versão é adequado para uma VPS com filesystem local; antes de múltiplos hosts, migrar ledger para banco compartilhado com constraints e locks. Não remover ledger para reenviar UNKNOWN; reconciliar confirmação Meta primeiro.

Escopo product/client da bridge é validado para vitrine-ai-social-enterprise e o token interno é obrigatório. Antes de abrir o receptor para outros produtos, usar identidade/token por produto e autorização de tenant sem confiar apenas no project_id recebido.

O adaptador legado da TV Sumaré permanece inalterado. Não ativar o candidato como correção automática da publicação da TV.

## Testes

Workflow marketing-native-publishing.yml: PHP, banco/Storage fake, Http::preventStrayRequests, nenhuma credencial real. Casos: formatos, canal escolhido, opt-in, aprovação/copy alterada, isolamento de produtos, conflito de chave, timeout sem retry, container pendente sem novo upload e consulta sem side effect. Lint dos arquivos tocados e compilação Blade.

Complementa cschibelsky-star/vitrine-ai-social-enterprise PR #9. CI não comprova HML operacional na VPS; ativação e validação do fluxo entre os dois produtos permanecem etapas pendentes.

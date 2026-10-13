# Conheça Sumaré — Descubra Sumaré de um novo jeito

**Revisão editorial:** 13/10/2026, America/Sao_Paulo.
**Estado:** textos revisados para produção; campanha NÃO aprovada; mídia final NÃO produzida por esta revisão.
**Destino principal informado:** https://www.conhecasumare.com.br/
**Escopo:** cinco Feeds, cinco sequências de três Stories, três Reels e três capas independentes.
**Origem:** proposta Avello preservada em `conheca-sumare-avello-original-20261013.md` e no commit 2498186d564cfaf7a4ae4d15458c8da028080a8c.
**Auditoria e fontes:** [conheca-sumare-auditoria-20261013.md](conheca-sumare-auditoria-20261013.md).
**Fila de produção:** [conheca-sumare-producao-20261013.json](conheca-sumare-producao-20261013.json). Contrato documental de preparação, sem importação automática comprovada.

## Briefing operacional

```yaml
briefing_status: NEEDS_INPUT
brand_or_product: "Conheça Sumaré"
campaign_name: "Descubra Sumaré de um novo jeito"
business_objective: "Gerar visitas qualificadas ao portal"
communication_objective: "Apresentar conteúdos de lugares, cultura, lazer, agenda e negócios"
audience: "Moradores de Sumaré e visitantes da RMC"
channels: ["Instagram", "Facebook"]
formats: ["5 Feed 1080x1080", "15 Stories 1080x1920", "3 Reels MP4 1080x1920", "3 capas 1080x1920"]
main_message: "Descubra Sumaré de um novo jeito"
cta: "Acesse conhecasumare.com.br"
tone: "Local, acolhedor, informativo; sem superlativos ou promessas não comprovadas"
visual_direction_hint: "Identidade canônica Conheça Sumaré; fotografia documental; verde/teal; sem estética tecnológica de outra marca"
duration_seconds: null
aspect_ratio: "1:1 e 9:16 conforme peça"
assets:
  - name: "Assinatura original Conheça Sumaré e tokens canônicos"
    status: NECESSARIO
  - name: "Logo original Vitrine IA Pro para crédito técnico, quando aplicável"
    status: NECESSARIO
  - name: "Fotos e vídeos reais com licença para campanha"
    status: NECESSARIO
  - name: "Capturas atuais do portal com mídias de terceiros liberadas"
    status: NECESSARIO
constraints:
  - "Revisão humana na galeria antes de publicar"
  - "Preservar arquivos e revisões anteriores"
  - "Uma peça final por arquivo"
forbidden_claims_or_elements:
  - "Portal oficial da Prefeitura, parceria ou endosso governamental"
  - "Fotos inventadas ou reconstruções por IA de locais reais"
  - "Empresas como parceiras sem comprovação"
  - "Contatos de terceiros coletados na internet"
  - "Vagas, benefícios, visitas empresariais, descontos ou oportunidades disponíveis sem prova"
missing_inputs:
  - "Comprovação de licença de cada imagem, vídeo, trilha e voz"
  - "Validação visual e funcional da versão pública atual"
  - "Evento específico confirmado, vigente e publicado para F04/S04"
source_facts:
  - "Fonte HML canônica consultada possui /explorar, /eventos, /empresas e seção #historia"
  - "Acervo Factory marca imagens selecionadas como provisórias e não autorizadas"
  - "Casarão Sertãozinho consta no Portal Turismo municipal; existência não comprova visitação"
```

## Direção visual e acessibilidade

Preservar a homepage canônica aprovada e o logotipo original. Não copiar o template legado encontrado no HostGator, nem transformar a campanha em dark high-tech da Vitrine IA Pro. O código HML usa tema teal #006e68; o CSS contém múltiplas gerações de estilos. A antiga paleta sugerida no rascunho não é prova de identidade aprovada. Extrair os tokens efetivamente aplicados da versão pública antes da arte final; não substituir o kit de marca por cores arbitrárias.

Feed 1080×1080: margem mínima 72 px; título de referência 64–80 px, complemento 32–40 px, assinatura/CTA 30–36 px. Stories/Reels 1080×1920: texto entre y=250 e y=1620, margem lateral 72 px; título 64–88 px e corpo/legendas 40–48 px, verificando preview da plataforma. Reservar espaço para sticker de link. Contraste mínimo 4,5:1 no texto comum e 3:1 no texto grande; usar faixa sólida ou overlay quando a fotografia prejudicar leitura. Reels com legendas sincronizadas e áudio conferido. Texto alternativo deve descrever a foto efetivamente usada, sem inferir local ou pessoas.

Cada conjunto tem assunto, enquadramento e composição próprios. Story exige layout vertical específico; não é um recorte do Feed. Manter nome do local junto à fotografia somente após conferir a correspondência. Não alterar fachadas, paisagens, monumentos ou sinalização para produzir uma versão fictícia.

## Feed — copy revisada

| ID | Título | Complemento | Legenda | CTA | Destino planejado |
|---|---|---|---|---|---|
| F01 | Conheça Sumaré | Descubra sua cidade de um novo jeito | Um novo olhar começa perto de você. Explore conteúdos sobre lugares, cultura e experiências locais no Conheça Sumaré. Acesse conhecasumare.com.br. | Acesse conhecasumare.com.br | Home informada |
| F02 | Cada lugar conta uma história | Cultura e memória de Sumaré | A história de Sumaré também está em seus espaços e patrimônios. Explore os conteúdos de cultura e história do Conheça Sumaré. Antes de visitar um local, confirme as condições de acesso. | Explore cultura e história | /#historia, previsto no código HML |
| F03 | Seu próximo passeio pode ser por aqui | Ideias para explorar Sumaré | Procurando inspiração para passear? Conheça os atrativos apresentados no portal e escolha o que combina com você. Confirme horários, acesso e condições de visitação antes de sair. | Encontre ideias de passeio | /explorar, previsto no código HML |
| F04 | Antes de sair, confira a programação | Data, horário, local e organização | Consulte a agenda do Conheça Sumaré e confirme a programação com o organizador ou na fonte indicada. Datas e horários podem mudar. | Confira a agenda | /eventos, previsto no código HML |
| F05 | Sumaré também é destino de negócios | Comércio, indústria e serviços | Conheça conteúdos sobre empresas e atividades econômicas de Sumaré. Explore a área de negócios do portal e descubra outra perspectiva da cidade. | Explore a área de negócios | /empresas, previsto no código HML |

**Gate de copy:** destinos internos são candidatos identificados no código, ainda sem validação HTTP/visual pública. Se não houver conteúdo correspondente acessível, segurar a peça; não encaminhar uma chamada específica para seção inexistente. Feed/Reel: CTA em domínio visível e link na bio somente se a bio da conta tiver sido conferida. Stories: usar sticker com o destino aprovado.

**Gate F04:** manter em espera até verificar /eventos com ao menos uma ocorrência publicada, fonte primária, data completa, horário, local e confirmação. A legenda é de serviço; não significa que existe evento hoje. Não usar “hoje”, “neste fim de semana”, “próximo evento” ou “últimas vagas” sem ocorrência específica e vigente. Nunca transformar a recorrência da Feira de Artesanato em confirmação de edição. Sem evento confirmado, omitir F04/S04 da veiculação e ajustar o calendário editorial, preservando ambos os rascunhos.

## Stories — quinze telas

| Sequência | Tela 1 | Tela 2 | Tela 3 / CTA | Direção e gate |
|---|---|---|---|---|
| S01 | Você já olhou Sumaré de um novo jeito? | Lugares, histórias e experiências locais | Conheça Sumaré. Acesse o portal. | Foto urbana real autorizada; composição panorâmica vertical; sticker Home |
| S02 | Uma cidade, muitas histórias | Cultura e memória de Sumaré | Explore cultura e história no Conheça Sumaré. | Foto cultural real identificada; detalhe arquitetônico; sticker /#historia após validação |
| S03 | Procurando ideias para passear? | Conheça os atrativos apresentados no portal | Confira as informações antes de sair. Encontre ideias de passeio. | Foto de natureza real conferida; plano aberto diferente de S01; sticker /explorar |
| S04 | Antes de sair, confira a programação | Confirme data, horário, local e organização | Veja a agenda do Conheça Sumaré. | Gate de evento vigente; layout de serviço; sticker /eventos |
| S05 | Sumaré também é destino de negócios | Comércio, indústria e serviços | Explore a área de negócios do portal | Foto empresarial local licenciada; sem “parceiro”; sticker /empresas |

Uma tela = um PNG. Nomes S01-01 a S05-03. Nenhuma tela de produção pode conter instruções internas, campos de preenchimento ou fotos genéricas identificadas como locais reais. F04/S04 não reutilizam uma imagem de feira antiga como se documentasse a ocorrência atual.

## Reels — roteiros revisados e planos de cena

### R01 — Descubra de novo, 30 s

| Tempo | Cena | Texto na tela |
|---|---|---|
| 0–5 s | Plano urbano real, licenciado, local identificado internamente | Você conhece sua cidade? |
| 5–12 s | Detalhe cultural real autorizado, diferente do hero de F02 | Histórias |
| 12–19 s | Natureza real autorizada, diferente do hero de F03 | Lugares e experiências |
| 19–25 s | Gravação atual do portal validado; mídias de terceiros também liberadas | Comece a explorar |
| 25–30 s | Assinatura original Conheça Sumaré e domínio | Descubra Sumaré de um novo jeito |

Narração: “Olhe Sumaré por um novo ângulo. Descubra lugares, conheça histórias e encontre ideias para explorar a cidade. Antes de visitar, confirme as informações do local. Conheça Sumaré. Acesse conhecasumare.com.br.”

Legenda: “Um novo olhar sobre lugares, cultura e experiências locais. Descubra Sumaré de um novo jeito em conhecasumare.com.br.”

### R02 — Ideias para passear, 25 s

| Tempo | Cena | Texto na tela |
|---|---|---|
| 0–5 s | Navegação real em /explorar, após validação | Ideias para passear em Sumaré |
| 5–10 s | Foto real licenciada de um atrativo publicado; movimento sutil, sem gerar paisagem | Natureza |
| 10–15 s | Foto cultural real licenciada de outro atrativo publicado | Cultura e memória |
| 15–20 s | Conteúdo real do portal, com fotos liberadas | Confira acesso e horários |
| 20–25 s | Marca original e domínio | Encontre ideias de passeio |

Narração: “Quer ideias para o próximo passeio? Explore os atrativos apresentados no Conheça Sumaré. Veja o que combina com você e confirme horários, acesso e condições de visitação antes de sair. Acesse conhecasumare.com.br.”

Legenda: “Explore os atrativos do portal e confira as condições de visitação antes de sair. conhecasumare.com.br.”

### R03 — Cidade em movimento, 30 s

| Tempo | Cena | Texto na tela |
|---|---|---|
| 0–6 s | Cena urbana real distinta de R01, licenciada | Sumaré por outros olhares |
| 6–13 s | Comércio/serviços reais; pessoas identificáveis com liberação aplicável | Quem faz a cidade acontecer |
| 13–20 s | Atividade econômica local comprovada e mídia autorizada | Comércio, indústria e serviços |
| 20–25 s | Navegação real em /empresas, validada | Explore negócios |
| 25–30 s | Marca e domínio | Conheça Sumaré |

Narração: “Sumaré se revela na cultura e em quem faz a cidade acontecer. Conheça conteúdos sobre comércio, indústria e serviços e descubra outras perspectivas da cidade no Conheça Sumaré. Acesse conhecasumare.com.br.”

Legenda: “Comércio, indústria e serviços também fazem parte de Sumaré. Explore a área de negócios em conhecasumare.com.br.”

Gravar voz em pt-BR e ajustar leitura ao tempo real, sem acelerar artificialmente para cumprir duração. Música, voz e B-roll exigem direitos documentados. Entregar MP4 real, capa independente e SRT. Não usar apresentador/avatar fixo de outra marca sem decisão editorial. Não simular interface do portal, visitas a fábricas, parcerias ou pessoas locais. Fotografias podem receber movimento de edição; não gerar pessoas, locais ou alterações estruturais.

## Ordem editorial de sete dias — proposta, sem datas ou agendamento

| Dia relativo | Conteúdo | Condição |
|---|---|---|
| 1 | F01 + S01 | Identidade, links e foto liberados |
| 2 | R01 | Vídeos/fotos, captura e áudio liberados; QA |
| 3 | F02 + S02 | Local cultural e imagem conferidos |
| 4 | F03 + S03 | Atrativos, acesso e link conferidos |
| 5 | F04 + S04 | Evento vigente, publicado e confirmado; sem confirmação, manter em espera |
| 6 | F05 + S05 | Página /empresas e mídia empresarial conferidas |
| 7 | R02 | Aprovação específica do vídeo |

R03 continua na fila de produção e aprovação como reserva editorial; sua data será definida depois, sem substituir ou excluir R02. Não confundir produção de três Reels com veiculação obrigatória dos três em sete dias. A janela começa somente após aprovação; conferir novamente a agenda imediatamente antes da liberação. Nenhuma conta, data ou horário foi cadastrado.

## Passagem ao Marketing IA e galeria

1. Receber os textos e o manifesto documental; manter a seleção de motor/provedor no Centro IA.
2. Resolver identidade e ativos necessários; autorizações ligadas ao arquivo exato por hash, autor, origem, escopo de uso e validade.
3. Produzir individualmente F01–F05, S01-01–S05-03, R01–R03 e capas. Priorizar o primeiro conjunto e verificar identidade antes dos demais.
4. Registrar ID real de execução quando houver. Sem retorno do sistema, não marcar “importado”, “gerado” ou “na galeria”.
5. Galeria deve receber peça, copy, CTA/destino, texto alternativo ou legendas, fontes/licenças e QA; aprovação individual com responsável, horário e versão/hash.
6. Ajustes preservam original e criam nova versão; aprovação anterior não vale automaticamente para a versão alterada.
7. Distribuição usa a plataforma própria autorizada. Agendar somente quando houver aprovação editorial, arquivos finais e conta/destino validados. Não encaminhar ao Metricool por padrão.

## Liberação

- [ ] Versão pública atual, identidade e rotas verificadas visualmente
- [ ] Arquivos reais, autoria, identificação do local e licença conferidos
- [ ] Sem imagens do acervo marcadas não autorizadas
- [ ] Capturas do portal não reproduzem fotos de terceiros sem licença
- [ ] Evento vigente conferido e ausência de promessa “hoje” sem prova
- [ ] Nenhuma alegação de Prefeitura/parceria/resultado/vaga/benefício não comprovado
- [ ] Uma peça por arquivo; composição própria; sem regressão de identidade
- [ ] PNG/MP4/capas/SRT e áudio revisados; texto alternativo, contraste e legendas
- [ ] Cada peça aprovada na versão correta e confirmação da aprovação na galeria
- [ ] Agendamento separado e condicionado à aprovação

## Evidência histórica da geração

O rascunho registra geração Avello com `gemini-3.1-flash-lite`, 473 tokens de entrada, 5184 de saída (incluindo 1204 de raciocínio), total 5657. É registro herdado; esta revisão não confirma cobrança nem realiza chamada ao provedor.

# Auditoria editorial — Conheça Sumaré — 13/10/2026

## Resultado e limites

Revisão parcial da versão pública; textos e preparação documental concluídos, liberação de mídia bloqueada por direitos e validação atual do portal. PR #106 permanece rascunho. Nenhuma produção, importação na galeria, aprovação, agendamento ou publicação é demonstrada por este documento.

A proposta original tem cinco Feeds, cinco sequências de três Stories e três Reels. Preservar os arquivos já produzidos fora deste PR; eles não foram inventariados ou substituídos nesta revisão. O PR inicial altera somente um documento. A cópia original é preservada byte a byte em `conheca-sumare-avello-original-20261013.md`; não representa nova aprovação.

## Evidências consultadas

| Evidência | Resultado | Limite |
|---|---|---|
| [PR #106](https://github.com/cschibelsky-star/vitrine-ai-pro/pull/106), head 2498186d564cfaf7a4ae4d15458c8da028080a8c | Aberto, draft, um arquivo editorial | Não contém artes finais |
| https://www.conhecasumare.com.br/ | Leitura web sem conteúdo utilizável; acesso local negado por política de rede; browser ERR_BLOCKED_BY_CLIENT | Não demonstra indisponibilidade do portal para o usuário |
| https://conhecasumare.com.br/ | Consulta indexada encontrou homepage com HML 4.1.0-PREMIUM-HML; abertura direta não acessível | Cache de busca não comprova versão pública atual, redirecionamento www, imagem ou link funcional |
| Probe do Super nos dois hosts | hostname_not_allowed | Política do conector, não erro do portal |
| V5 project_status com ID visite-sumare | manifest_not_found | Não estabelece projeto operacional atual |
| HostGator, root conhecasumare.com.br | index.php, app/views/header.php, assets/css/style.css, app/bootstrap.php e storage/data lidos | Artefato legado 2.0; não houve prova de que esse root atende a produção VPS |
| [index.php HML canônico](https://github.com/cschibelsky-star/vitrine-ai-pro/blob/deploy/conheca-sumare-hml/index.php), blob 8eda56aabef5da3b8d4f94a9730cfff5b149d289 | Rotas e referências 4.3.0-ECONOMIC-HML identificadas | Código HML não equivale a deploy público |
| [assets/style.css HML](https://github.com/cschibelsky-star/vitrine-ai-pro/blob/deploy/conheca-sumare-hml/assets/style.css), blob 35781ee11f9cad767573eaa9b0284f2ea828fb26 | Múltiplas gerações CSS; identidade teal/verde e regras faithful-home | Validar os estilos efetivamente aplicados na produção antes da arte final |
| [Controle de imagens Factory](https://github.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/blob/main/products/guia-digital-turismo/implementacao_4_3/data/image_sources.json), blob 55d19336a6def5c9c361f8c5db0567f407880a88 | 25 registros com provisoria_referencia, fonte/crédito vazios e imagem_autorizada=nao | Nenhum desses 25 registros autoriza campanha; nome capa-real.jpg não comprova autenticidade |
| [Importação de fontes oficiais](https://github.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/blob/main/products/guia-digital-turismo/implementacao_4_3/README-IMPORTACAO-FONTES-OFICIAIS-4.3.txt) | Exige validação formal do material importado | URL oficial não é licença automática |
| [Casarão Sertãozinho, Turismo municipal](https://turismo.sumare.sp.gov.br/atrativo-historico/casarao-sertaozinho) | Existência e referência histórica confirmadas na página consultada | Visitação e licença da foto não confirmadas |
| [Agenda municipal](https://turismo.sumare.sp.gov.br/eventos) | Lista sazonal e recorrências de feiras | Não confirma uma ocorrência futura com data/horário na campanha |

## Links e correspondência editorial

| Material | Destino | Evidência de correspondência | Estado |
|---|---|---|---|
| F01/S01/R01 | https://www.conhecasumare.com.br/ | URL fornecida no briefing | PENDENTE_HTTP_VISUAL_ATUAL |
| F02/S02 | https://www.conhecasumare.com.br/#historia | id=historia no código HML | PENDENTE_HTTP_VISUAL_ATUAL |
| F03/S03/R02 | https://www.conhecasumare.com.br/explorar | Handler explorar no HML | PENDENTE_HTTP_VISUAL_ATUAL |
| F04/S04 | https://www.conhecasumare.com.br/eventos | Handler eventos no HML | PENDENTE_HTTP_VISUAL_ATUAL_E_EVENTO |
| F05/S05/R03 | https://www.conhecasumare.com.br/empresas | Handler empresas no HML, conteúdo econômico | PENDENTE_HTTP_VISUAL_ATUAL |
| Links individuais de atrativos | Não usados como CTA nesta revisão | Parte dos cartões HML ainda usa href=# e “Conteúdo em atualização” | Não inventar URL de detalhe; validar antes de citar |

Usar /empresas para Negócios; não reintroduzir cadastro-empresa.php. Não afirmar cadastro funcional, passaporte, benefícios, vagas ou visita industrial. Não tratar #mapa como prova de mapa funcional: no código consultado ele identifica seção de oportunidades. A imagem institucional de case em main/assets/img/cases/conheca-sumare-showcase.jpg é descrita em casos-reais.php como representação ilustrativa; não deve representar Sumaré real nem ser captura de produto.

## Atrativos e imagens

**Nenhuma foto de local foi confirmada como autorizada para campanha nesta auditoria.** Candidatos abaixo podem ser usados apenas após comprovação vinculada ao arquivo exato e identificação visual. Nenhuma mídia foi gerada ou editada.

Prefixo Factory: products/guia-digital-turismo/implementacao_4_3/.

| Candidato | Arquivo / origem identificada | Comprovação atual | Uso planejado / decisão |
|---|---|---|---|
| Bosque dos Lagos Cidade Orquídea / Jardim Dall’Orto | images/atrativos/bosque-dallorto/capa-real.jpg; blob 5899b4e019f2c50b7dba9ae1ef339f2beaee5543 | imagem_autorizada=nao; fonte/crédito vazios | F01/S01 ou F03/S03, escolher enquadramentos distintos; não liberado |
| Bosque, alternativa municipal | https://turismo.sumare.sp.gov.br/public/img/natural_atractives/bosque-dos-lagos-sumare.jpeg | URL no código HML; licença e inspeção visual pendentes | Referência; conferir nome e fotografia; não liberado |
| Associação Pró-Memória de Sumaré | images/atrativos/pro-memoria/capa-real.jpg; blob 96f23b4c6264bc0e5153d5a10dc42b1af777894e | imagem_autorizada=nao | F02/S02; confirmar autor/origem e correspondência; não liberado |
| Represa Marcelo Pedroni | images/atrativos/represa-marcelo-pedroni/capa-real.jpg; blob 2701dfa65a05297a3ea9299b68af5314bd4a3cc4 | imagem_autorizada=nao | F03/S03/R02; conferir acesso atual; não liberado |
| Praça das Bandeiras | images/atrativos/praca-das-bandeiras/capa-real.jpg; blob 7c8f2f650c1d3c0bb6b05509e87912a477c06b9a | imagem_autorizada=nao | Plano urbano alternativo; não confundir com Praça da República |
| Casarão Sertãozinho, acervo | images/atrativos/casarao-sertaozinho/capa.jpg; blob 9ec1adcc3ccc80d8216b7acd8268a56ddc6716d1 | imagem_autorizada=nao | F02/S02 como alternativa após licença; não prometer visitação |
| Casarão, alternativa municipal | https://turismo.sumare.sp.gov.br/public/img/historic_atractives/casarao-sertaozinho.jpeg | Origem municipal identificada no HML; licença pendente | Preservar referência canônica para Cultura e História; não liberado |
| Shopping ParkCity Sumaré | images/atrativos/shopping-parkcity/capa.jpg; blob c377fd9fd12228750bb28a6d63c2f590e855e42d | imagem_autorizada=nao | F05/S05/R03 após autorização; não declarar parceiro |
| Coca-Cola FEMSA | https://coca-colafemsa.com/en/join-coca-cola-femsa/apply-here/assets/img/unete-a-coca-cola-femsa/BRASIL/kofbra-hero-banner-desk-1.jpg | Fonte empresarial no HML; não prova que a fotografia é da unidade Sumaré nem licença | Bloqueada para retratar Sumaré sem correspondência e direitos; não prometer visita ou parceria |
| Feira de Artesanato | images/eventos/feira-artesanato/capa-real.jpg; blob a3a48652ea8f1aeed009968a8476552edc6eb6d3 | imagem_autorizada=nao; edição/data não identificadas | F04/S04 bloqueados; foto histórica não prova ocorrência futura |
| Hero portal | assets/img/hero-real.jpg; blob 298dbdd9638129523a4030e07d20778fbddb5b20 | Nome/local/autoria/licença não estabelecidos | Não reutilizar na campanha sem comprovação |
| SVGs do root legado | assets/img/bosque.svg, memoria.svg, feira.svg e semelhantes | Referências do template; conteúdo SVG não inspecionado por restrição de tipo | Não substituem fotografias documentais |
| Capturas do portal | Ainda não capturadas nesta revisão | Sem captura atual ou auditoria dos direitos das mídias embutidas | Necessárias em R01/R02/R03; não contornam direitos de fotos |
| Marca Conheça Sumaré / crédito Vitrine IA Pro | Assinatura e referência ao logo original no HML | Identidade própria; arquivo final e validação visual pendentes | Utilizar originais; não gerar logotipo por IA |

Expo Águas não entra na campanha; sua presença no controle legado não restaura o conteúdo removido. “Hotel Participante”, “Parceiro Destaque” e nomes genéricos dos dados legados não são prova de negócio real. Não reutilizar contatos do acervo ou da fonte pública.

Para liberar uma mídia: registrar asset_id, nome do local conferido, URL de origem, autor/crédito, arquivo original e hash, evidência de autorização/licença, uso orgânico/pago, plataformas permitidas, recorte/edição permitidos, prazo e direitos de pessoas/marcas quando aplicáveis. Sem comprovante, manter em espera. Preservar versões existentes.

## Agenda

O root HostGator contém somente “Agenda Cultural”, “Feiras e encontros” e “Cultura e memória”, com datas “Atualização oficial”, “Programação local” e “Permanente”. Esses registros não são ocorrências com data/horário e não autorizam chamada para evento atual.

O código HML filtra status=published e datas start_date/end_date em America/Sao_Paulo, mas não foi validada a execução atual nem uma ocorrência publicada real. A agenda municipal lista meses e recorrências, incluindo Feira de Artesanato; isso serve como referência de pauta. Não converter automaticamente recorrência em evento confirmado e não usar programação do Dia das Crianças após 12/10 como futura sem nova edição comprovada.

F04/S04: exigir evento_id real, título, fonte primária, início/fim, horário, local, conferência com organizador, estado publicado e evidência da presença na /eventos pública. Conferir novamente antes de liberar; retirar da fila de veiculação quando vencido, cancelado ou sem confirmação, mantendo o rascunho.

## Correções aplicadas

- Removidas chamadas de “oportunidades” como oferta disponível; F05/S05/R03 promovem conteúdo econômico.
- Removido “Tudo para começar a explorar”, substituído por chamada proporcional ao conteúdo.
- R02 passou de três locais obrigatórios para dois locais comprováveis mais navegação; não força uso de imagem sem licença.
- Inseridas condições de acesso/visitação, sem horários inventados.
- Agenda separada com gate obrigatório; sem urgência e sem imagem genérica identificada como evento atual.
- Paleta anterior mantida apenas no arquivo histórico; produção depende da identidade efetivamente aplicada.
- Links internos documentados como candidatos HML, sem afirmar 200 OK.
- R03 preservado na produção como reserva editorial, com data a definir.
- Fluxo preparado para Marketing IA e galeria; distribuição pela plataforma própria, somente depois da aprovação.

## Estados e próximo trabalho

Textos: REVISADOS_PARA_PRODUCAO. Briefing: NEEDS_INPUT. Mídias finais: NAO_PRODUZIDAS_NESTA_REVISAO. Materiais prévios: PRESERVAR_SEM_SUBSTITUIR; inventário externo pendente. Galeria: NAO_IMPORTADO. Aprovação: PENDENTE. Agendamento/publicação: NAO_EXECUTADOS.

Próximas dependências: confirmar runtime/versão pública e links, fornecer ou localizar direitos comprovados dos ativos, produzir peças individuais, QA e aprovação específica na galeria. Esta revisão não altera portal, runtime, infraestrutura, dados de eventos ou arquivos de mídia.

# Comparação do Assessor Online — 14/10/2026

## Escopo e evidência
Comparação das imagens fornecidas do Gabinete Online com as branches feature/assessor-online-landing-hml, feature/assessor-online-trial-hml e feature/cockpit-assessor-online-hml do vitrine-ai-pro. Pesquisa de repositórios por assessor/gabinete encontrou assessorgov-ia, produto separado de oportunidades públicas, não substituto de gestão parlamentar. A comparação do concorrente é visual; não comprova comportamento ou segurança internos.

Runtime inspecionado: a2b4ad481fcfc876b85dccb9ebc4e9806d372e32. A branch remota trial tinha 10 commits posteriores; esta proposta parte do runtime preservado, sem incorporar alterações não revisadas.

## Matriz
| Frente | Existente neste escopo | Lacuna / prioridade |
|---|---|---|
| Apresentação | Landing e demonstração estática | Evoluir página seguindo logo próprio; P1 |
| Dashboard | Exemplos fictícios | Indicadores reais, ações, prazos; P0 |
| Atendimento | Fluxo conceitual | CRUD, responsáveis, transições, histórico, devolutiva; P0 |
| Contatos | Não encontrado módulo operacional | Cadastro, região, preferências, deduplicação; P0 |
| Acesso | Gate, vínculo de empresa e acesso do trial | RBAC operacional e isolamento por gabinete; P0 |
| Agenda | Não encontrado | Compromissos, lembretes, responsável; P1 |
| Documentos | Minutas descritas | Modelos, versões, revisão, encaminhamentos; P1 |
| Portal cidadão | Conceito | Protocolo e consulta autorizada sem vazamento; P1 |
| Relatórios | Números estáticos | Período, região, assunto, equipe, exportação; P1 |
| Comunicação | Integração futura | Preferências, devolutivas, aprovação, entrega/retry; P2 |
| IA | Conceito | Resumo, sugestões e minutas revisadas via integração existente; P2 |
| Trial | Código, testes, flag desligada | Adaptador real, revogação e Cockpit ainda exigem homologação |

## Critérios do painel
Navegação por tarefas, configuração separada da operação, filtros claros, estado vazio e erro, foco visível, uso em 320/390/1440 pixels. Visão geral deve priorizar demandas vencidas, sem responsável e aguardando devolutiva. Métricas devem ter definição e origem verificável. Nenhum número de demonstração deve aparecer como resultado real.

## Implementado nesta proposta
Painel demonstrativo navegável com quatro exemplos sintéticos, busca que ignora acentos, filtro por situação, resultado vazio, três áreas e identidade do logo original. Não é CRUD, autenticação, IA ativa ou integração de envio. Arquivos operacionais e flag do trial preservados. Mantida referência Rafael Monteiro utilizada pelo smoke test existente.

## Sequência funcional
1. Consolidar modelo de gabinete, usuário, contato, demanda e eventos com isolamento e testes negativos.
2. Implementar cadastro e acompanhamento do atendimento; somente concluir após registrar resolução e devolutiva.
3. Conectar dashboard a consultas reais e validar filtros, paginação e datas no fuso local.
4. Documentos e agenda; depois portal, relatórios e comunicação.
5. Homologar Provider/Cockpit e autorizar produção em gate separado.

## Limites
Nenhum módulo ausente neste escopo foi declarado inexistente em todo o ecossistema. Confirmar inventário antes de reutilizar componentes do Core. Não copiar ativos, marcas, preços ou promessas do concorrente. Sem merge, migrations de produção ou ativação do trial.

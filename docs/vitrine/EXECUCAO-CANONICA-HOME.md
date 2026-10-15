# Execução canônica — Home institucional Vitrine IA Pro

**Atualizado:** 2026-10-15. **Situação:** NÃO HOMOLOGADA. **Prioridade exclusiva:** concluir a Home fiel à arte aprovada, na HML existente.

## Contrato imutável da entrega
- Única referência: `Vitrine IA Pro_ Tecnologia com Propósito.png`, 1024×1536, SHA-256 `c9b53dda1b4deb67a124955bcd84da978eea70f08a348cce78ff4162bf7a63b3`.
- Página HTML/CSS/JS real, funcional, responsiva; **não** screenshot como website, nem redesenho, nem aproximação por CSS dos pixels originais.
- Header, hero (globo + skyline originais), quatro projetos na ordem Guia Digital / TV Digital / Governo Digital IA / SISMED (em desenvolvimento), cases Conheça Sumaré e TV Sumaré, conceito e footer conforme referência.
- Usar arte/marca aprovada, imagens oficiais com origem e contexto; não inventar contatos, resultados ou filiação oficial.
- HML canônica: `https://site.hml.vitrineiapro.com.br/`, projeto operacional `vitrine-site-hml`.
- Preservar produção e demais produtos; não abrir novas HML/infraestruturas, nem alterar DNS, fazer merge/cutover sem validação.

## Referências e estado conferido em 15/10/2026
- Código visual: `cschibelsky-star/vitrine-ai-pro`, PR #115 (draft), branch `design/vitrine-showcase-approved-20261014`, HEAD `895d5fef906dfcf1e3b2d6cf06bfab32f4eb0605`.
- Checkout HML: projeto `vitrine-site-hml`, branch atual `design/vitrine-showcase-approved-20261014`, HEAD `f467d4a9c69a1e4536e494ca9b79c140b4fe3a2c`. **Não confundir** com remote-tracking `feature/vitrine-site-vps-runtime`, cuja divergência já foi observada. Não reconciliar à força.
- Asset Intake: `cschibelsky-star/VitrineAI-Factory`, PR #16 (draft), branch `feat/factory-asset-ingestion-20261014`, HEAD `5f31f72aa8140739b2cabf6f8566ea1864c03e85`. Upload binário, validação hash, ACL e CI desenvolvidos.
- VPS validador isolado `factory-asset-intake-validation-20261015`: build e execução de testes com exit code 0 confirmados após sincronização com `5f31f72a`.
- V5 vivo: `0.5.14-runtime-secret-import`. O código de host inspecionado instancia FastMCP sem provedor de auth demonstrado; binding da Factory **recusa ativação** sem token validado pelo SDK.
- PR VPS #27 tem política proposta mas não ativa no V5 compartilhado; não reconstruir o V5 a partir de fonte divergente, não expor endpoint sem autenticação.
- Na HML: imagem de hero aprovada em `/assets/img/home/hero-approved.webp` **não encontrada (404)** na última prova. JPGs dos cases são placeholders; layout não aprovado.
- Verificadores de assets e workflow de gate visual já criados no PR #115; seu estado de falha por falta de recursos não constitui a conclusão da página.

## Etapas únicas — atualizar status com EVIDÊNCIA, nunca apenas alegação
| ID | Entrega | Estado | Aceite objetivo |
|---|---|---|---|
| H01 | Referência original e 7 recursos preparados | PARCIAL | Bytes persistidos, SHA conferido e disponíveis para transferência em meio autorizado |
| H02 | Canal binário autenticado operacional | BLOQUEADO | Upload real com identidade/ACL/TTL, hash e receipt, sem alteração do V5 compartilhado não homologada |
| H03 | Recursos originais versionados | PENDENTE | GitHub apresenta arquivos oficiais, SHA registrado; sem substituição por similar |
| H04 | Home HTML/CSS atualizada | PENDENTE | Todos os elementos originais carregam, interação e navegação preservadas |
| H05 | HML reconciliada com segurança | PENDENTE | Git limpo, preservação de estado, HTTP 200 de página/assets, sem alteração produção |
| H06 | Fidelidade visual homologada | PENDENTE | Screenshots reais 1024/1440/390 comparados à referência, teste responsivo, navegação/acessibilidade |
| H07 | Publicação produção | BLOQUEADA | Somente após H01–H06 comprovadas e autorização específica de publicação |

## Regra operacional anti-repetição
1. Antes de qualquer alteração, ler este arquivo, os PRs e o HEAD da HML. Identificar o **primeiro gate realmente pendente**; não reabrir decisões fechadas.
2. Corrigir o bloqueio mais próximo da entrega. Não criar outra Factory, HML, mockup, design, documentação equivalente nem rodar novamente teste já comprovado sem mudança de versão.
3. Para cada mudança: registrar data, comando/ação autorizada, commit, health, hashes e link de evidência. Marcar como concluído SOMENTE o que estiver comprovado.
4. Em falha: registrar qual ferramenta/rota falhou, se houve mutação parcial e o fallback testado; preservar arquivos/branches sujos.
5. Não repetir `EXECUTAR` sem avanço: reportar uma ação específica ainda necessária e por quê.

## Histórico sintético
- 14/10: PR #115 implementou estrutura HTML/CSS/JS; arte original do hero e cases não foi versionada; referência aprovada preservada no contrato `docs/vitrine/site-referencia-visual-canonica-20261014.md`.
- 15/10: PR Factory #16 validou pipeline em CI e runner isolado na VPS, mas **não** instalou upload autenticado no host canônico.
- 15/10: PR VPS #27 propôs permitir runner na política do V5; política do processo não foi ativada; Super executou o runner isolado sem alterar V5.
- 15/10: PR Home #115 recebeu gate visual; passagens de CI técnico não equivalem a aceitação visual. HML continua sem hero oficial.
- 15/10: este registro canônico consolida o ponto exato de continuidade e evita reiniciar investigações.

## Próxima ação operacional válida
**Resolver H02 com a integração autenticada já construída no PR #16**, preferindo um host isolado e sem afetar o V5 compartilhado. Testar transferência integral do arquivo canônico, publicar os assets no GitHub (H03) e só depois alterar a Home, sincronizar HML e comparar screenshots. Sem alternativa autorizada que receba bytes, registrar bloqueio real em vez de anunciar entrega.

**Definição de pronto:** a Home aprovada é visível na HML, todos os recursos são os originais, há comparação visual comprovada e nenhuma regressão. Até lá: `NÃO HOMOLOGADA`.

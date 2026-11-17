# Escritório Virtual DEV/ECO — Estado central (2026-11-17)

## Objetivo aprovado
Escritório visual e operacional de agentes IA, com departamentos DEV e ECO segregados, missões reais, ferramentas autorizadas, evidências, seleção multimodelo e supervisão independente da VIA. A interface deve refletir eventos reais; animações não comprovam execução.

## Fonte de verdade
Repositório cschibelsky-star/vitrine-ai-pro. Esta branch consolida somente documentação. Não substitui branches de implementação e não autoriza deploy.

## Evidências verificadas no GitHub
- docs/escritorio-agentes-ia-base-20261116: arquitetura, departamentos, roadmap, handoff, inventário; contém 45 commits à frente de main na comparação consultada.
- feature/cockpit-escritorio-agentes-m1-hml-20261116: view Blade, rota e cartão HML preparados; divergiu de main (65 à frente/60 atrás).
- feature/cockpit-escritorio-m1-preserved-hml-20261116: versão composta preservando arquivos anteriores, view Blade, rota e cartão; divergiu de main (67 à frente/60 atrás). É a referência mais avançada da integração visual documentada, NÃO do motor real.
- backup/cockpit-hml-pre-escritorio-20261116: backup parcial de arquivos preexistentes, conforme COCKPIT-HML-PRESERVATION.md.
- docs/hub-ia-001-governance-20261114: branch relacionada ao roteamento multimodelo; não equivale a runtime integrado ao Escritório.
- database/seeders/AiAgentSeeder.php na branch docs/escritorio-agentes-ia-base-20261116: nove definições ECO. Agentes DEV operacionais não comprovados.

## Artefatos externos ao GitHub
- Escritorio-Agentes-IA-M1-offline.zip: entregue em conversa; README da branch de base declara que frontend offline foi entregue como ZIP, não commitado ali.
- Escritorio-Agentes-IA-M2-auditado.zip: incremento offline gerado no ChatGPT; NÃO confirmado como commit ou runtime GitHub.
- Vídeo de escritório isométrico: referência de UX, não implementação.

## Estado de capacidade
| Capacidade | Estado |
|---|---|
| Arquitetura DEV/ECO | Documentada |
| Nove definições ECO | Código localizado |
| UI M1 offline | Código GitHub na branch HML preservada e ZIP da conversa |
| Rota/cartão Cockpit HML | Preparados em branch; publicação NÃO comprovada |
| Agentes DEV provisionados | Não comprovado |
| Motor de missões com persistência | Não comprovado |
| Execução real de ferramentas por missão | Não comprovado |
| HUB-IA-001 integrado | Não comprovado |
| VIA com gate autenticado | Não comprovado |
| E2E HML e produção | Não comprovados |

## Próxima prioridade
Inventário dos serviços reais e contratos de execução (DEV/ECO), depois implementação isolada de uma missão ponta a ponta com eventos, evidências e gate independente; somente então integração visual e homologação autorizada.

## Limites
Sem merge, deploy, migrations, custos de API ou alterações de runtime. Não tratar número de commits como prova de funcionalidade. Comparações são snapshots da consulta de 2026-11-17.

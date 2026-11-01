# Verificação integrada local — 2026-11-01

## Origem
Arquivos das branches de trabalho lidos novamente via GitHub:
- TVSUMARE_ENTERPRISE PR #45: `includes/indoor_catalog_export.php`.
- vitrine-ai-pro PR #136: `docs/indoor/pilot-adapter/src/TvSumareIndoorAdapter.php`.

## Execução
Com PHP 8.4.24, executei uma reprodução local da lógica consultada (não um checkout integral do GitHub), com:
- notícia aprovada para `indoor_only`;
- anúncio aprovado para `both` na posição lateral;
- duplicata, dados extras privados, item sem consentimento, mídia expirada e caminho inválido.

Resultados:
- `php -l` aprovado para os dois componentes;
- teste ponta a ponta local passou, 7 asserções;
- resultado de importação permanece `pending_indoor_review`;
- não há exportação de campos privados adicionais no contrato.

## Limites
- Execução em cópias locais dos códigos, sem clone/checkout exato da branch, sem dados reais, sem autenticação HTTP e sem execução verificada no CI.
- Os PRs continuam em draft. Não houve merge, deploy, migração ou publicação.
- Não representa homologação de segurança, VPS, isolamento entre clientes, CDN ou cache offline.
- **Obrigatório antes de distribuir mídia:** autenticação de serviço, escopo de tenant, revisão de domínio/IP/redirecionamentos, auditoria de consentimento e testes HML.

## Próximo gate
1. Rodar os testes PHP no CI de cada repositório, com versões reais das branches;
2. preparar contrato de transporte autenticado sem endpoint público;
3. validar mapeamento da biblioteca da TV Sumaré e confirmar branch canônica HML;
4. homologar importação em HML com dados de teste, sem publicar automaticamente.

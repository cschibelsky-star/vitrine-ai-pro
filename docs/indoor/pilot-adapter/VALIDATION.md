# Validação local do protótipo (29/10/2026)

- PHP CLI: 8.4.24.
- Sintaxe: aprovada.
- Teste funcional do algoritmo do adaptador com fixtures simuladas: OK.
- Cenários: notícia aprovada, anúncio lateral, status pendente, consentimento ausente, direitos expirados, URL file://, duplicidade, tenant vazio e estado pendente de revisão.

**Limite da evidência:** a validação foi executada em cópia local da lógica do adaptador, não em checkout integral do PR, e não comprova execução do CI, instalação em HML, disponibilidade de endpoint, dados reais, nem segurança de redes/SSRF. Nenhuma publicação foi efetuada.

## Bloqueios de homologação
1. Comparar versão real da HML TV Sumaré com a main GitHub; os arquivos includes/home_featured.php e includes/home_feature_ads.php observados anteriormente na implantação não aparecem na main consultada.
2. Confirmar esquema de autorização Indoor e a fonte administrativa da videoteca, incluindo conteúdo não publicado.
3. Antes de qualquer fetch remoto, aplicar allowlist de domínios, DNS/IP público, proteção contra redirects e restrições de mídia; HTTPS no schema sozinho não evita SSRF.
4. Executar testes no checkout exato em CI, incluindo tentativas de cruzar tenants e expiração durante offline.
5. Somente depois disponibilizar o endpoint de leitura autenticado em HML e testar ponta a ponta.

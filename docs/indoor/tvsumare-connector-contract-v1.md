# Vitrine Indoor IA — contrato inicial do conector TV Sumaré (HML)

Referência: issue #134. Documento de desenho técnico, **sem endpoint implementado** e sem dados de produção.

## Observações confirmadas na main de TVSUMARE_ENTERPRISE (29/10/2026)
- `index.php` lê `data/noticias.json` e aplica `tvs_prepare_public_news_v2` / `tvs_prepare_public_news` antes de compor hero e listas públicas.
- `admin/area-comercial.php` lê `data/anuncios_pedidos.json`; registra estados `aprovado`, `publicado` e `pagamento confirmado`. Estado comercial não equivale a direito para veicular em Indoor.
- `videos.php` chama `tvs_load_real_videos(0)` e mostra vídeos públicos. Não representa necessariamente biblioteca de vídeos privados ou não publicados.
- Arquivos `includes/home_featured.php` e `includes/home_feature_ads.php` **não foram encontrados na main** consultada; não inferir equivalência com instalação na VPS/produção.

## Responsabilidades separadas
**TV Sumaré:** gestão editorial/comercial própria e consentimento explícito para exportar; não sofrer alterações no seu fluxo de publicação.

**Vitrine Indoor IA:** plataforma SaaS; TV Sumaré é tenant/piloto 001; importa catálogo de itens *elegíveis*; revisão e aprovação Indoor separadas; programa por aparelho/grupo; distribui manifestos ao player Web/PWA.

## Contrato proposto de item (v1; sujeito a auditoria)
```json
{
  "schemaVersion": "indoor-import.v1",
  "source": "tvsumare",
  "sourceId": "id-canonico-na-origem",
  "sourceVersion": "versao-ou-hash",
  "type": "news",
  "title": "Titulo do material",
  "summary": "Resumo adaptado",
  "media": [{"kind": "image", "url": "https://example.invalid/image.jpg", "sha256": null}],
  "originEditorialState": "approved",
  "indoorConsent": {"allowed": false, "approvedAt": null, "approvedBy": null},
  "rights": {"distribution": "indoor", "validUntil": null},
  "display": {"slot": "central", "suggestedDurationSec": 18},
  "updatedAt": "2026-10-29T00:00:00Z"
}
```
Os valores acima são **exemplo de esquema**, não conteúdo ou URLs reais.

## Regras de elegibilidade
1. Somente `indoorConsent.allowed=true` após autorização verificável.
2. Origem `approved` para conteúdo editorial comum; itens `indoor_only` requerem fluxo exclusivo de aprovação Indoor.
3. Pauta bruta do Radar pendente, rejeitada ou descartada não pode ser distribuída.
4. Pedidos de anúncios, status de pagamento ou `publicado` na TV Sumaré **não autorizam automaticamente** veiculação Indoor.
5. Direitos comerciais, vigência, formato, mídia íntegra e acesso ao tenant devem ser verificados antes de compor manifesto.
6. Reimportação idempotente por `(source, sourceId, sourceVersion)`; revogação invalida próxima versão do manifesto.
7. Canal e destino: `tvsumare_only`, `indoor_only`, `both`, com aprovações independentes.

## API planejada (ainda não implementada)
- `GET /api/indoor/v1/imports/tvsumare?cursor=...`: somente backend autenticado da plataforma; paginação e escopo; não expor CRM, contatos ou dados de pedidos.
- `POST /api/indoor/v1/catalog/{id}/approve`: usuário autorizado aprova uso Indoor; trilha de auditoria.
- `POST /api/indoor/v1/schedules`: agenda por local/aparelho, janelas e prioridade.
- `GET /api/indoor/v1/devices/{id}/manifest`: token específico de dispositivo, ETag, manifesto imutável e verificações de integridade.
- `POST /api/indoor/v1/devices/{id}/ack`: informa download/ativação/execução registrada (não comprova imagem exibida fisicamente).

## Testes mínimos antes da habilitação
- Permissões de tenant A contra tenant B; origem comercial não autoriza publicação; notícia não aprovada rejeitada.
- Conteúdo `indoor_only` aprovado aparece só na Indoor.
- Reimportação não duplica; expiração e revogação retiram o item; falta de mídia não ativa nova playlist.
- Publicidade lateral e conteúdo central operam independentemente conforme referência V4.4.
- Teste ponta a ponta Windows + TV Box/PWA, queda de conexão, reconexão, vídeos longos, início após reboot.
- Verificar diferenças entre main GitHub e implantação HML antes de qualquer adaptação.

**Governança:** este documento não habilita exportação, publicação, merge, migration nem deployment.

# Android — Conheça Sumaré

O PWA hospedado em `https://conhecasumare.com.br/` é a base oficial do aplicativo Android.

## Identidade Android

- Package/applicationId oficial: `br.com.conhecasumare.app`
- Host oficial: `conhecasumare.com.br`
- Start URL: `https://conhecasumare.com.br/?source=android`
- Estratégia inicial: Trusted Web Activity (TWA), mantendo o PWA como fonte única de interface e conteúdo.

## Assinatura e Digital Asset Links

A TWA só deve ser considerada verificada depois que o APK/AAB for assinado e o fingerprint SHA-256 real do certificado estiver publicado em:

`https://conhecasumare.com.br/.well-known/assetlinks.json`

Não publique um fingerprint fictício. O SHA-256 deve ser extraído do certificado realmente usado no build Android.

Para restrições de chave do Google Maps no Android, use o package `br.com.conhecasumare.app` e o SHA-1 do mesmo certificado de assinatura.

## Sequência de homologação

1. Validar manifest, service worker, instalação e fallback offline do PWA.
2. Gerar o projeto TWA com o package oficial.
3. Gerar/selecionar o certificado de homologação.
4. Extrair SHA-1 e SHA-256 do certificado.
5. Configurar a restrição Android da chave Google com package + SHA-1.
6. Publicar `.well-known/assetlinks.json` com o SHA-256.
7. Gerar APK/AAB e testar em aparelho físico.
8. Somente depois promover a mesma cadeia de assinatura para produção/Play Console.

## Regras

- Não duplicar conteúdo do portal dentro do Android.
- Mudanças de interface permanecem no PWA sempre que não exigirem API nativa.
- Recursos nativos entram de forma incremental: notificações, câmera/QR, compartilhamento, localização e integrações específicas.

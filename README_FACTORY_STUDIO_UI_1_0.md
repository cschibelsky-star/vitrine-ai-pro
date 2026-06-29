# FACTORY STUDIO UI 1.0

## Objetivo

Finalizar a primeira versão operacional da Factory no painel Filament.

Depois deste pacote, o fluxo deixa de depender apenas do SSH e passa a ter uma tela visual:

```txt
Admin → Factory Studio
```

## Entregas

1. Página Filament `Factory Studio`
2. Campo para solicitação livre
3. Botão `Produzir`
4. Botão `Simular instalação`
5. Botão `Ver último relatório`
6. Registro visual do produto resolvido, status e caminhos de relatório

## Pré-requisito

Este pacote depende dos comandos já homologados:

```bash
php artisan factory:produce-request "..."
php artisan factory:install-system gov360 --dry-run
```

## Instalação

Extraia este ZIP na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 factory_studio_ui_bootstrap.py
composer dump-autoload
php artisan optimize:clear
```

## Acesso

Depois entre no painel:

```txt
https://app.vitrineiapro.com.br/admin
```

Procure no menu:

```txt
Factory → Factory Studio
```

## Teste

Na tela, use:

```txt
Quero um sistema para pequenas empresas venderem para o governo
```

Clique em:

```txt
Produzir
```

Depois clique em:

```txt
Simular instalação GOV360
```

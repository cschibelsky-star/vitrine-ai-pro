# FACTORY FINAL MASTER 1.0

## Objetivo

Consolidar tudo em uma única versão final.

## Comando principal

### Simulação segura

```bash
php artisan factory:build-and-install "Quero um sistema para clínicas veterinárias com agenda, clientes, prontuários, vacinas e financeiro" --dry-run
```

### Instalação real, depois da validação

```bash
php artisan factory:build-and-install "Quero um sistema para clínicas veterinárias com agenda, clientes, prontuários, vacinas e financeiro" --force --migrate
```

## O que o comando executa

1. `factory:finalize-request`
2. identifica o blueprint gerado
3. `factory:real-build`
4. `factory:enterprise-build`
5. `factory:real-install --dry-run` ou instalação real
6. `factory:enterprise-install --dry-run` ou instalação real
7. `composer dump-autoload`, `optimize:clear` e `migrate` quando solicitado
8. relatório final único

## Instalação do pacote

Extraia na raiz:

```txt
/home1/cris1649/vitrine-ai-pro
```

Depois rode:

```bash
cd /home1/cris1649/vitrine-ai-pro
python3 factory_final_master_bootstrap.py
composer dump-autoload
php artisan optimize:clear
php artisan list | grep "build-and-install"
```

## Homologação

```bash
php artisan factory:build-and-install "Quero um sistema para clínicas veterinárias com agenda, clientes, prontuários, vacinas e financeiro" --dry-run
```

## Relatórios

```txt
storage/app/factory/final-master/
```

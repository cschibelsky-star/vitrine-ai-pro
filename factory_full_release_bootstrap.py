from pathlib import Path

provider = Path("app/Factory/Providers/FactoryServiceProvider.php")
required = [
    provider,
    Path("app/Factory/Console/Commands/FactoryProduceEnterpriseCommand.php"),
    Path("app/Factory/ProductionEnterprise/Services/EnterpriseProductionEngine.php"),
    Path("config/factory_enterprise_products.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes; comando não registrado. Faltando: " + ", ".join(missing))

text = provider.read_text()

imp = "use App\\Factory\\Console\\Commands\\FactoryProduceEnterpriseCommand;\n"

if imp not in text:
    marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
    if marker in text:
        text = text.replace(marker, marker + imp)
    else:
        text = text.replace("<?php\n", "<?php\n\n" + imp)

cmd = "FactoryProduceEnterpriseCommand::class"

if cmd not in text:
    if "FactorySyncCommand::class" in text:
        text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")
    elif "$this->commands([" in text:
        text = text.replace("$this->commands([", f"$this->commands([{cmd}, ")
    else:
        raise SystemExit("Bloco de commands não localizado.")

provider.write_text(text)

print("Factory Full Release aplicado com sucesso.")
print("Comando principal disponível: php artisan factory:produce gov360")

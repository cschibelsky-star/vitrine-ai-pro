from pathlib import Path

provider = Path("app/Factory/Providers/FactoryServiceProvider.php")

required = [
    provider,
    Path("app/Factory/FinalProducer/Services/ProductRequestResolver.php"),
    Path("app/Factory/FinalProducer/Services/ProduceRequestPipeline.php"),
    Path("app/Factory/FinalProducer/Services/SystemInstallPlanner.php"),
    Path("app/Factory/Console/Commands/FactoryProduceRequestCommand.php"),
    Path("app/Factory/Console/Commands/FactoryInstallSystemCommand.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes; comandos não registrados. Faltando: " + ", ".join(missing))

text = provider.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryProduceRequestCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryInstallSystemCommand;\n",
]

for imp in imports:
    if imp not in text:
        marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
        if marker in text:
            text = text.replace(marker, marker + imp)
        else:
            text = text.replace("<?php\n", "<?php\n\n" + imp)

for cmd in ["FactoryProduceRequestCommand::class", "FactoryInstallSystemCommand::class"]:
    if cmd not in text:
        if "FactorySyncCommand::class" in text:
            text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")
        elif "$this->commands([" in text:
            text = text.replace("$this->commands([", f"$this->commands([{cmd}, ")
        else:
            raise SystemExit("Bloco de commands não localizado.")

provider.write_text(text)
print("Factory Final Producer registrado com sucesso.")
print("Comandos: factory:produce-request, factory:install-system")

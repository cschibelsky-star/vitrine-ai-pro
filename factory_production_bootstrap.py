from pathlib import Path

provider = Path("app/Factory/Providers/FactoryServiceProvider.php")
if not provider.exists():
    raise SystemExit("FactoryServiceProvider não encontrado.")

text = provider.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryProduceCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryProductionStatusCommand;\n",
]

for imp in imports:
    if imp not in text:
        marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
        if marker in text:
            text = text.replace(marker, marker + imp)
        else:
            text = text.replace("<?php\n", "<?php\n\n" + imp)

commands = ["FactoryProduceCommand::class", "FactoryProductionStatusCommand::class"]

for cmd in commands:
    if cmd not in text:
        if "FactorySyncCommand::class" in text:
            text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")
        elif "$this->commands([" in text:
            text = text.replace("$this->commands([", f"$this->commands([{cmd}, ")
        else:
            raise SystemExit("Bloco de commands não localizado.")

provider.write_text(text)
print("Factory Production Engine registrado com sucesso.")
print("Comandos registrados: factory:produce, factory:production-status")

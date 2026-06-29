from pathlib import Path
import re

provider = Path("app/Factory/Providers/FactoryServiceProvider.php")

if not provider.exists():
    raise SystemExit("FactoryServiceProvider não encontrado.")

text = provider.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryReleaseStatusCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryDecisionCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryWorkflowCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryProductCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryDocsCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryHistoryCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryEvolutionCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactorySmartQa2Command;\n",
]

for imp in imports:
    if imp not in text:
        marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
        if marker in text:
            text = text.replace(marker, marker + imp)
        else:
            text = text.replace("<?php\n", "<?php\n\n" + imp)

commands = [
    "FactoryReleaseStatusCommand::class",
    "FactoryDecisionCommand::class",
    "FactoryWorkflowCommand::class",
    "FactoryProductCommand::class",
    "FactoryDocsCommand::class",
    "FactoryHistoryCommand::class",
    "FactoryEvolutionCommand::class",
    "FactorySmartQa2Command::class",
]

for cmd in commands:
    if cmd not in text:
        if "FactorySyncCommand::class" in text:
            text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")
        elif "$this->commands([" in text:
            text = text.replace("$this->commands([", f"$this->commands([{cmd}, ")
        else:
            raise SystemExit("Bloco de commands não localizado.")

provider.write_text(text)

print("FACTORY_MACRO_PACK_01 v3.0 registrado com sucesso.")
print("Comandos registrados:")
for cmd in commands:
    print(" - " + cmd)

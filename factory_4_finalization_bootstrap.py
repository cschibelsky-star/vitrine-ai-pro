from pathlib import Path

provider = Path("app/Factory/Providers/FactoryServiceProvider.php")

required = [
    provider,
    Path("config/factory_4_domains.php"),
    Path("app/Factory/Finalization/Services/AiArchitectFinalService.php"),
    Path("app/Factory/Finalization/Services/FinalizationProductionService.php"),
    Path("app/Factory/Finalization/Services/SmartFinalInstallerService.php"),
    Path("app/Factory/Console/Commands/FactoryArchitectRequestCommand.php"),
    Path("app/Factory/Console/Commands/FactoryFinalizeRequestCommand.php"),
    Path("app/Factory/Console/Commands/FactoryInstallFinalCommand.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes; comandos não registrados. Faltando: " + ", ".join(missing))

text = provider.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryArchitectRequestCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryFinalizeRequestCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryInstallFinalCommand;\n",
]

for imp in imports:
    if imp not in text:
        marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
        if marker in text:
            text = text.replace(marker, marker + imp)
        else:
            text = text.replace("<?php\n", "<?php\n\n" + imp)

for cmd in [
    "FactoryArchitectRequestCommand::class",
    "FactoryFinalizeRequestCommand::class",
    "FactoryInstallFinalCommand::class",
]:
    if cmd not in text:
        if "FactorySyncCommand::class" in text:
            text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")
        elif "$this->commands([" in text:
            text = text.replace("$this->commands([", f"$this->commands([{cmd}, ")
        else:
            raise SystemExit("Bloco de commands não localizado.")

provider.write_text(text)

print("Factory 4.0 Finalization Pack aplicado com sucesso.")
print("Comandos disponíveis:")
print(" - factory:architect-request")
print(" - factory:finalize-request")
print(" - factory:install-final")

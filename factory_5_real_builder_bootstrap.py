from pathlib import Path

provider = Path("app/Factory/Providers/FactoryServiceProvider.php")

required = [
    provider,
    Path("app/Factory/RealBuilder/Services/RealBuilderNameService.php"),
    Path("app/Factory/RealBuilder/Services/RealCodeGenerator.php"),
    Path("app/Factory/RealBuilder/Services/RealBuildInstaller.php"),
    Path("app/Factory/RealBuilder/Services/FinishProjectService.php"),
    Path("app/Factory/Console/Commands/FactoryRealBuildCommand.php"),
    Path("app/Factory/Console/Commands/FactoryRealInstallCommand.php"),
    Path("app/Factory/Console/Commands/FactoryFinishProjectCommand.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes; comandos não registrados. Faltando: " + ", ".join(missing))

text = provider.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryRealBuildCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryRealInstallCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryFinishProjectCommand;\n",
]

for imp in imports:
    if imp not in text:
        marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
        if marker in text:
            text = text.replace(marker, marker + imp)
        else:
            text = text.replace("<?php\n", "<?php\n\n" + imp)

for cmd in [
    "FactoryRealBuildCommand::class",
    "FactoryRealInstallCommand::class",
    "FactoryFinishProjectCommand::class",
]:
    if cmd not in text:
        if "FactorySyncCommand::class" in text:
            text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")
        elif "$this->commands([" in text:
            text = text.replace("$this->commands([", f"$this->commands([{cmd}, ")
        else:
            raise SystemExit("Bloco de commands não localizado.")

provider.write_text(text)

print("Factory 5.0 Real Builder Engine aplicado com sucesso.")
print("Comandos disponíveis:")
print(" - factory:real-build")
print(" - factory:real-install")
print(" - factory:finish-project")

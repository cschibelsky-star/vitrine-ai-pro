from pathlib import Path

provider = Path("app/Factory/Providers/FactoryServiceProvider.php")

required = [
    provider,
    Path("app/Factory/EnterpriseMaturity/Services/EnterpriseNameService.php"),
    Path("app/Factory/EnterpriseMaturity/Services/EnterpriseCodeGenerator.php"),
    Path("app/Factory/EnterpriseMaturity/Services/EnterpriseBuildInstaller.php"),
    Path("app/Factory/Console/Commands/FactoryEnterpriseBuildCommand.php"),
    Path("app/Factory/Console/Commands/FactoryEnterpriseInstallCommand.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes; comandos não registrados. Faltando: " + ", ".join(missing))

text = provider.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryEnterpriseBuildCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryEnterpriseInstallCommand;\n",
]

for imp in imports:
    if imp not in text:
        marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
        if marker in text:
            text = text.replace(marker, marker + imp)
        else:
            text = text.replace("<?php\n", "<?php\n\n" + imp)

for cmd in [
    "FactoryEnterpriseBuildCommand::class",
    "FactoryEnterpriseInstallCommand::class",
]:
    if cmd not in text:
        if "FactorySyncCommand::class" in text:
            text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")
        elif "$this->commands([" in text:
            text = text.replace("$this->commands([", f"$this->commands([{cmd}, ")
        else:
            raise SystemExit("Bloco de commands não localizado.")

provider.write_text(text)

print("Factory Enterprise Maturity Pack aplicado com sucesso.")
print("Comandos disponíveis:")
print(" - factory:enterprise-build")
print(" - factory:enterprise-install")

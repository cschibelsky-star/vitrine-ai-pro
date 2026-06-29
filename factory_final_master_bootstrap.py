from pathlib import Path

provider = Path("app/Factory/Providers/FactoryServiceProvider.php")

required = [
    provider,
    Path("app/Factory/FinalMaster/Services/FactoryFinalMasterService.php"),
    Path("app/Factory/Console/Commands/FactoryBuildAndInstallCommand.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes; comando não registrado. Faltando: " + ", ".join(missing))

text = provider.read_text()

imp = "use App\\Factory\\Console\\Commands\\FactoryBuildAndInstallCommand;\n"

if imp not in text:
    marker = "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n"
    if marker in text:
        text = text.replace(marker, marker + imp)
    else:
        text = text.replace("<?php\n", "<?php\n\n" + imp)

cmd = "FactoryBuildAndInstallCommand::class"

if cmd not in text:
    if "FactorySyncCommand::class" in text:
        text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")
    elif "$this->commands([" in text:
        text = text.replace("$this->commands([", f"$this->commands([{cmd}, ")
    else:
        raise SystemExit("Bloco de commands não localizado.")

provider.write_text(text)

print("Factory Final Master aplicado com sucesso.")
print("Comando disponível: factory:build-and-install")

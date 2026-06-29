from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

for imp in [
    "use App\\Factory\\Console\\Commands\\FactoryDetectCompatibilityCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryQaModuleCommand;\n",
]:
    if imp not in text:
        text = text.replace("use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n", "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n" + imp)

for cmd in ["FactoryDetectCompatibilityCommand::class", "FactoryQaModuleCommand::class"]:
    if cmd not in text:
        text = text.replace("FactorySyncCommand::class", f"FactorySyncCommand::class, {cmd}")

path.write_text(text)
print("Factory BUILD 004.1 registrada no FactoryServiceProvider.")

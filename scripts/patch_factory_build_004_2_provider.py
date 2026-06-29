from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryMakeBlueprintCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryMakeSystemCommand;\n",
]

for imp in imports:
    if imp not in text:
        text = text.replace(
            "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n",
            "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n" + imp
        )

for cmd in ["FactoryMakeBlueprintCommand::class", "FactoryMakeSystemCommand::class"]:
    if cmd not in text:
        text = text.replace(
            "FactorySyncCommand::class",
            f"FactorySyncCommand::class, {cmd}"
        )

path.write_text(text)
print("Factory BUILD 004.2 registrada no FactoryServiceProvider.")

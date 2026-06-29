from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryAiBlueprintCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryAiPlanCommand;\n",
]

for imp in imports:
    if imp not in text:
        text = text.replace(
            "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n",
            "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n" + imp
        )

for cmd in ["FactoryAiBlueprintCommand::class", "FactoryAiPlanCommand::class"]:
    if cmd not in text:
        text = text.replace(
            "FactorySyncCommand::class",
            f"FactorySyncCommand::class, {cmd}"
        )

path.write_text(text)
print("Factory BUILD 006 registrada no FactoryServiceProvider.")

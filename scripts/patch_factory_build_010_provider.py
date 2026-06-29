from pathlib import Path

path = Path("app/Factory/Providers/FactoryServiceProvider.php")
text = path.read_text()

imports = [
    "use App\\Factory\\Console\\Commands\\FactoryDomainKnowledgeCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryComponentsForDomainCommand;\n",
    "use App\\Factory\\Console\\Commands\\FactoryArchitectureDesignCommand;\n",
]

for imp in imports:
    if imp not in text:
        text = text.replace(
            "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n",
            "use App\\Factory\\Console\\Commands\\FactorySyncCommand;\n" + imp
        )

for cmd in [
    "FactoryDomainKnowledgeCommand::class",
    "FactoryComponentsForDomainCommand::class",
    "FactoryArchitectureDesignCommand::class",
]:
    if cmd not in text:
        text = text.replace(
            "FactorySyncCommand::class",
            f"FactorySyncCommand::class, {cmd}"
        )

path.write_text(text)
print("Factory BUILD 010 registrada no FactoryServiceProvider.")

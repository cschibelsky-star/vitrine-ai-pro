from pathlib import Path

page = Path("app/Filament/Pages/FactoryStudio.php")
view = Path("resources/views/filament/pages/factory-studio.blade.php")

missing = [str(p) for p in [page, view] if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes. Faltando: " + ", ".join(missing))

print("Factory Studio UI instalado.")
print("Acesse: /admin")
print("Menu: Factory -> Factory Studio")

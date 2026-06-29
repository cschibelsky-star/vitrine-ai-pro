from pathlib import Path
from datetime import datetime
import shutil

provider = Path("app/Providers/Filament/AdminPanelProvider.php")
if not provider.exists():
    raise SystemExit("AdminPanelProvider.php não encontrado.")

backup_dir = Path("storage/app/factory/backups/enterprise_rc3")
backup_dir.mkdir(parents=True, exist_ok=True)
backup_file = backup_dir / f"AdminPanelProvider_{datetime.now().strftime('%Y%m%d_%H%M%S')}.php"
shutil.copy(provider, backup_file)

required = [
    Path("app/Providers/Filament/AdminPanelProvider.php"),
    Path("app/Filament/Pages/Dashboard.php"),
    Path("resources/views/filament/pages/dashboard.blade.php"),
]

missing = [str(p) for p in required if not p.exists()]
if missing:
    raise SystemExit("Arquivos obrigatórios ausentes: " + ", ".join(missing))

print("Enterprise 6.0 RC3 Clean Provider aplicado.")
print("Backup criado em:", backup_file)
print("Agora rode:")
print("composer dump-autoload")
print("php artisan optimize:clear")

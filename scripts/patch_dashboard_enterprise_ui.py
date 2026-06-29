from pathlib import Path
from datetime import datetime
import shutil, re
root = Path.cwd()
backup_dir = root / 'storage/app/factory/backups/dashboard_enterprise' / datetime.now().strftime('%Y%m%d_%H%M%S')
backup_dir.mkdir(parents=True, exist_ok=True)
candidates=[root/'app/Filament/Pages/AiDashboard.php', root/'app/Filament/Pages/Dashboard.php']
target=next((c for c in candidates if c.exists()), None)
if target is None:
    raise SystemExit('Nenhuma página de dashboard encontrada em app/Filament/Pages.')
shutil.copy2(target, backup_dir/target.name)
text=target.read_text()
if 'protected static string $view' in text:
    text=re.sub(r"protected static string \$view\s*=\s*['\"][^'\"]+['\"];", "protected static string $view = 'filament.pages.ai-dashboard-enterprise';", text)
elif 'protected static ?string $view' in text:
    text=re.sub(r"protected static \?string \$view\s*=\s*['\"][^'\"]+['\"];", "protected static string $view = 'filament.pages.ai-dashboard-enterprise';", text)
else:
    text=text.replace('{\n', "{\n    protected static string $view = 'filament.pages.ai-dashboard-enterprise';\n", 1)
target.write_text(text)
print(f'Dashboard Enterprise aplicado em: {target}')
print(f'Backup criado em: {backup_dir}')

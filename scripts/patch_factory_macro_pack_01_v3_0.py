from pathlib import Path
import subprocess
import sys

bootstrap = Path("factory_release_bootstrap.py")

if not bootstrap.exists():
    raise SystemExit("factory_release_bootstrap.py não encontrado.")

subprocess.check_call([sys.executable, str(bootstrap)])

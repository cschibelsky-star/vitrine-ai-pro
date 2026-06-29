from pathlib import Path
import subprocess
import sys

bootstrap = Path("factory_bootstrap.py")

if not bootstrap.exists():
    raise SystemExit("factory_bootstrap.py não encontrado na raiz.")

subprocess.check_call([sys.executable, str(bootstrap)])

from __future__ import annotations

import os
from typing import Any

import httpx

from server import mcp
import supervisor  # noqa: F401  # registra ferramentas do Supervisor IA
import workflow_catalog  # noqa: F401  # registra catálogo n8n
import factory_kernel  # noqa: F401  # registra Kernel discovery-first

BROKER_URL =
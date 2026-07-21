FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends docker.io git procps coreutils \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt
COPY server.py main.py ops_broker.py supervisor.py workflow_catalog.py factory_kernel.py project_registry.py ./

RUN useradd --create-home --uid 10001 connector

EXPOSE 8765 8770
CMD ["python", "main.py"]

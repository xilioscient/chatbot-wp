#!/usr/bin/env bash
set -e
BACKUP_DIR=${1:-./backups}
mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
cp -r docker/llm-service/data "$BACKUP_DIR/data_$TIMESTAMP"
echo "Backup salvato in $BACKUP_DIR/data_$TIMESTAMP"

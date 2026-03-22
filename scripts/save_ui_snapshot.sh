#!/bin/bash
# Save a timestamped backup of the deployed XAMPP copy and commit current workspace changes.
# Usage: ./scripts/save_ui_snapshot.sh "optional commit message"

set -euo pipefail
TS=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/Applications/XAMPP/htdocs/carochat-backups"
WORKSPACE_DIR="$(pwd)"
DEPLOY_DIR="/Applications/XAMPP/htdocs/carochat"

mkdir -p "$BACKUP_DIR"

echo "Creating tar.gz backup of deployed site -> $BACKUP_DIR/carochat-backup-$TS.tar.gz"
sudo tar -czf "$BACKUP_DIR/carochat-backup-$TS.tar.gz" -C "$(dirname "$DEPLOY_DIR")" "$(basename "$DEPLOY_DIR")"

echo "Backing up workspace (git commit & tag)"
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  git add -A
  MSG="$1"
  if [ -z "$MSG" ]; then
    MSG="DEV: snapshot $TS"
  fi
  # commit only if there are staged changes
  if ! git diff --cached --quiet; then
    git commit -m "$MSG"
    git tag -a "snapshot-$TS" -m "Snapshot $TS"
    echo "Committed workspace and created git tag: snapshot-$TS"
  else
    echo "No workspace changes to commit"
  fi
else
  echo "Not a git repo. Skipping git commit/tag"
fi

echo "Done. Backup saved at: $BACKUP_DIR/carochat-backup-$TS.tar.gz"

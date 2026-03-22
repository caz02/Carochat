#!/usr/bin/env bash
set -euo pipefail

# deploy_ui.sh
# Usage:
#   ./scripts/deploy_ui.sh        # dry-run (recommended)
#   ./scripts/deploy_ui.sh --deploy   # perform real deploy
#   ./scripts/deploy_ui.sh --help  # show this message

SRC_DIR="/Users/admin/Desktop/Projects/Carochat/"
DEST_DIR="/Applications/XAMPP/htdocs/carochat/"
BACKUP_DIR="/Applications/XAMPP/htdocs/carochat-backups"
APACHE_USER="daemon"

# Exclude patterns (relative to SRC_DIR)
EXCLUDES=( ".git" "node_modules" ".vscode" "uploads" "*.pem" "*.key" ".DS_Store" )

DRY_RUN=1
if [[ "${1:-}" == "--deploy" ]]; then
  DRY_RUN=0
fi
if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
  sed -n '1,120p' "$0"
  exit 0
fi

echo "Deploy script — src: $SRC_DIR -> dest: $DEST_DIR"

# create backup if dest exists
if [[ -d "$DEST_DIR" ]]; then
  echo "Creating backup of existing deploy..."
  sudo mkdir -p "$BACKUP_DIR"
  TS=$(date +%Y%m%d-%H%M%S)
  sudo tar -czf "$BACKUP_DIR/carochat-backup-$TS.tar.gz" -C "$(dirname "$DEST_DIR")" "$(basename "$DEST_DIR")"
  echo "Backup written: $BACKUP_DIR/carochat-backup-$TS.tar.gz"
fi

# build rsync exclude args
RSYNC_EXCLUDE_ARGS=()
for x in "${EXCLUDES[@]}"; do
  RSYNC_EXCLUDE_ARGS+=(--exclude="$x")
done

if [[ $DRY_RUN -eq 1 ]]; then
  echo "Performing rsync dry-run (no changes will be made). Review the file list below:"
  rsync -avz --delete "${RSYNC_EXCLUDE_ARGS[@]}" --progress "$SRC_DIR" "$DEST_DIR"
  echo "Dry-run completed. To perform the real deploy run: $0 --deploy"
  exit 0
fi

# Real deploy
echo "Running real deploy..."
# Use sudo rsync so ownership operations can be applied if needed
sudo rsync -avz --delete "${RSYNC_EXCLUDE_ARGS[@]}" --progress "$SRC_DIR" "$DEST_DIR"

# Ensure uploads folder exists and ownership is set for web server
sudo mkdir -p "$DEST_DIR/uploads"
# Only set ownership for the uploads folder (avoid changing other files that may be
# deployed with different ownership). This preserves other repo file owners.
sudo chown -R "$APACHE_USER":"$APACHE_USER" "$DEST_DIR/uploads"

# Restart Apache (XAMPP)
if [[ -x "/Applications/XAMPP/xamppfiles/bin/apachectl" ]]; then
  echo "Restarting Apache (XAMPP)..."
  sudo /Applications/XAMPP/xamppfiles/bin/apachectl restart || echo "apachectl restart returned non-zero"
else
  echo "apachectl not found at expected location; please restart Apache manually."
fi

# Restart signaling server using deployed copy (backgrounded)
echo "Restarting signaling server (node) using deployed copy..."
# kill old instances matching signaling-server.js
sudo pkill -f signaling-server.js || true
nohup sudo node "$DEST_DIR/signaling-server.js" > /tmp/carochat-signaling.log 2>&1 &
PID=$!
sleep 0.5
echo "Signaling server started (PID=$PID) — logs: /tmp/carochat-signaling.log"

echo "Deploy finished. Verify site at http://127.0.0.1/carochat/ and tail /tmp/carochat-signaling.log for signaling events."

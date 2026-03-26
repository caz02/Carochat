#!/usr/bin/env zsh
# start_dev.sh - start the PHP built-in server and open the site in Arc
# Usage: ./start_dev.sh [port]

PORT=${1:-8000}
ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
URL="http://127.0.0.1:$PORT"

# Check for PHP
if ! command -v php >/dev/null 2>&1; then
  echo "PHP not found. Install it with Homebrew: brew install php"
  exit 1
fi

# Start the PHP server in the background and capture the PID
echo "Starting PHP built-in server on $URL serving $ROOT_DIR"
php -S 127.0.0.1:$PORT -t "$ROOT_DIR" > /tmp/carochat.log 2>&1 &
SERVER_PID=$!

# Give the server a moment to start
sleep 0.5

# Check that the process is running
if ! kill -0 $SERVER_PID >/dev/null 2>&1; then
  echo "Failed to start PHP server. See /tmp/carochat.log for details."
  tail -n 100 /tmp/carochat.log
  exit 1
fi

# Try to open Arc; fall back to default browser
if open -a "Arc" "$URL" >/dev/null 2>&1; then
  echo "Opened $URL in Arc."
elif open -a "Arc Browser" "$URL" >/dev/null 2>&1; then
  echo "Opened $URL in Arc Browser."
else
  echo "Arc not found. Opening default browser instead."
  open "$URL"
fi

echo "Server started (PID: $SERVER_PID). To stop: kill $SERVER_PID"
echo "Server log: /tmp/carochat.log"

#!/usr/bin/env bash
set -euo pipefail

# deploy.sh — small, idempotent deployment helper for Ubuntu 22.04+ droplets
# Usage: run this on the DROPLET after cloning the repo (do NOT run on macOS)
# Example (on droplet):
#   sudo ./deploy.sh

if [[ "$(uname -s)" == "Darwin" ]]; then
  echo "This script must be run on a Linux server (Ubuntu). Aborting." >&2
  exit 1
fi

if [[ $(id -u) -ne 0 ]]; then
  echo "This script should be run with sudo or as root." >&2
  exit 1
fi

if ! command -v apt >/dev/null 2>&1; then
  echo "apt not found — this script expects an Ubuntu/Debian server. Aborting." >&2
  exit 1
fi

echo "Updating package lists..."
apt update -y
apt upgrade -y

echo "Installing prerequisites (docker, nginx, certbot)..."
# Docker install script (official)
curl -fsSL https://get.docker.com -o /tmp/get-docker.sh
sh /tmp/get-docker.sh

apt install -y docker-compose-plugin nginx certbot python3-certbot-nginx git

# Ensure docker service is running
systemctl enable --now docker

WORKDIR=$(pwd)
echo "Working directory: $WORKDIR"

read -rp "Enter your domain (e.g. example.com) that will point to this server: " DOMAIN
if [[ -z "$DOMAIN" ]]; then echo "Domain required"; exit 1; fi

read -rp "Enter DB root password to set for MySQL container (will be written to .env): " MYSQL_ROOT_PASSWORD
read -rp "Enter DB user to create (default: carouser): " DB_USER
DB_USER=${DB_USER:-carouser}
read -rp "Enter DB password for $DB_USER: " DB_PASS

cat > .env <<EOF
DB_HOST=db
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_NAME=carochat
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
EOF

chmod 600 .env || true

echo "Bringing up docker compose stack (may take a few minutes)..."
docker compose up -d --build

NGINX_CONF=/etc/nginx/sites-available/carochat
echo "Installing nginx site config to $NGINX_CONF"
cat > "$NGINX_CONF" <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    server_name ${DOMAIN} www.${DOMAIN};

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location /signaling {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    }

    location / {
        proxy_pass http://127.0.0.1:80;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    }
}
NGINX

ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/carochat
nginx -t && systemctl reload nginx

echo "Obtaining TLS certificate with certbot (interactive)..."
certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos -m "admin@${DOMAIN}" || {
  echo "certbot failed — you may need to run interactively: sudo certbot --nginx -d ${DOMAIN}" >&2
}

echo "Deployment complete. Visit: https://${DOMAIN}"
echo "Notes: .env was created in the repo root. Keep it secure and do not commit it to git."

# Deploying Carochat to an Ubuntu VPS

This file explains how to make the app public using a simple Ubuntu droplet.

Quick summary
- Create an Ubuntu 22.04 droplet and point your domain's A record to its IP.
- SSH into the droplet, clone this repo into /var/www, cd into the repo, and run the included `deploy.sh` script as root.

Example flow (on the droplet):

1. Clone the repo and run the script

```bash
cd /var/www
sudo git clone https://github.com/yourusername/Carochat.git
cd Carochat
sudo chmod +x deploy.sh
sudo ./deploy.sh
```

2. The script will:
- install Docker (official script), docker compose plugin, nginx, certbot
- prompt for your domain and DB passwords and create a `.env` file
- start the Docker Compose stack (web, db, signaling)
- install an nginx site and attempt to get TLS certificates via certbot

Notes & security
- Do NOT run `deploy.sh` on macOS — it is intended for Ubuntu servers.
- `deploy.sh` writes a `.env` file in the repository root. Keep that file out of git and secure it.
- Replace any weak passwords with strong secrets before sharing the site publicly.
- Remove any DB dumps from the repo if they contain real user data (we can help rewrite git history if needed).

If you prefer a manual step-by-step guide instead of running the script, contact me and I will provide copy/paste commands.

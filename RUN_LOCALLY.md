Run the project locally (macOS)

This repository contains a small helper script that starts PHP's built-in web server and opens the site in the Arc browser.

Files added:
- `start_dev.sh` — zsh script to start the PHP server and open Arc (or default browser)
- `RUN_LOCALLY.md` — these instructions

Quick steps

1) Make the script executable (one-time):

```zsh
cd /Users/admin/Desktop/Projects/Carochat
chmod +x start_dev.sh
```

2) Start the server and open Arc (default port 8000):

```zsh
./start_dev.sh
```

Or choose a port (e.g. 8080):

```zsh
./start_dev.sh 8080
```

What the script does
- Checks that `php` is installed. If missing, install via Homebrew: `brew install php`.
- Starts PHP's built-in server serving the repository root.
- Attempts to open the URL in `Arc` (or `Arc Browser`), falling back to the default browser.
- Logs server output to `/tmp/carochat.log`.

Stopping the server
- The script prints the server PID when it starts. Stop it with:

```zsh
kill <PID>
```

Troubleshooting
- If you see "PHP not found": install Homebrew then run `brew install php`.
- If Arc doesn't open, confirm Arc is installed (check `/Applications`) and the app name. You can open the URL manually with `open "http://127.0.0.1:8000"`.
- Check `/tmp/carochat.log` for server errors.

If you'd like, I can also add an npm-style `dev` script or a small `Makefile` entry instead — tell me which you prefer.

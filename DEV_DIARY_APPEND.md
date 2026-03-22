2026-03-22 — session notes (permissions, chat list, snapshot)

Summary of immediate changes during this session:

- Fixed file-system permissions for uploads so PHP can move uploaded files into `/Applications/XAMPP/htdocs/carochat/uploads` (used `chown -R daemon:daemon` + `chmod -R 755`).
- Removed the client-side mock chat fallback (the hard-coded sample contacts) so the left chat list only shows server-provided chats.
- Hardened client `fetchChats()` to return an empty list on failure and log a console warning (prevents debug/sample items from appearing).
- Added `scripts/save_ui_snapshot.sh` — a small snapshot helper that creates a timestamped tar.gz backup of the deployed site and optionally commits & tags the current workspace in git.

Quick commands (copy/paste) you can keep for future reference:

Create a one-off deployed backup (no git actions):

```bash
TS=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR=/Applications/XAMPP/htdocs/carochat-backups
mkdir -p "$BACKUP_DIR"
sudo tar -czf "$BACKUP_DIR/carochat-backup-$TS.tar.gz" -C /Applications/XAMPP/htdocs carochat
echo "Backup saved: $BACKUP_DIR/carochat-backup-$TS.tar.gz"
```

Use the reusable snapshot script added to the repo (recommended):

```bash
./scripts/save_ui_snapshot.sh "Optional commit message"
```

Make uploads writable by the Apache/PHP worker (the exact command used here):

```bash
sudo chown -R daemon:daemon /Applications/XAMPP/htdocs/carochat/uploads
sudo chmod -R 755 /Applications/XAMPP/htdocs/carochat/uploads
```

Verify the uploads ownership and modes:

```bash
ls -la /Applications/XAMPP/htdocs/carochat | grep uploads
ls -la /Applications/XAMPP/htdocs/carochat/uploads | head
```

If you'd like me to run the snapshot now (create the backup + commit/tag) say "run the snapshot" and I'll execute `./scripts/save_ui_snapshot.sh` and show the output.

End of 2026-03-22 append.

2026-03-22 14:30 — Make contact rows open chat on click

- Updated `includes/contacts.php` to render each contact row with a `userid` attribute and `onclick="start_chat(event)"` so selecting a contact opens the chat in the inner right panel.
- Hardened `start_chat` in `index.html` to use `e.currentTarget` (with fallbacks) so clicks on child elements (image, text, buttons) correctly resolve the target user id and open the chat.

How to test:
- Open the app locally, go to Contacts, click any contact. The chat should open in the right panel and messages load.
- If chat does not load, inspect DevTools → Network for the `api.php` call and Console for JSON/HTML errors (session/login redirects may cause HTML responses).

Notes:
- This is a low-risk change that only modifies server-rendered contact row HTML and client-side click handling. No new API endpoints were added.


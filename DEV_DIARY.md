# Carochat — dev diary (today)

Quick summary

I got the project running locally under XAMPP, fixed API JSON errors, and restored missing UI images so the web UI stopped crashing.

Main wins: fixed PHP output (no more HTML before JSON), preserved session cookies for API calls, and repaired a stray `/images` file that blocked the real images.

---

What I did (step‑by‑step)

- Found the app entry (`index.html` + `api.php`) and confirmed I was serving via XAMPP.
- Reproduced the errors in the browser and with curl (JSON.parse errors and 404s).
- Patched `api.php` so the API always returns JSON and PHP errors are logged (not printed).
- Hardened `index.html` JS to parse JSON safely and guard against missing DOM elements.
- Fixed some backend issues: corrected date format for signup and avoided deprecation warnings by initializing arrays properly.
- Found images under Apache was a 5‑byte file (not a folder). Moved it, created the real `images/` directory and copied the actual `male.jpg`.
- Verified login+user_info flows using curl with a cookie jar so the session persisted.
- Confirmed the image now loads and API returns clean JSON.

Problems I ran into

- JSON.parse “Unexpected token '<'” — caused by PHP warnings/HTML being printed before JSON.
- 404s for `male.jpg` — missing file on the server.
- `cp` failed with “Not a directory” — the `images` path on the server was an actual file (5 bytes) not a folder.
- Signup was failing due to wrong datetime format.
- PHP deprecation warning from treating false as an array.
- Separate curl calls used different sessions because cookies weren’t preserved.

How I fixed each one (short)

- HTML in JSON: changed `api.php` to disable `display_errors`, enable `log_errors`, start session early, and set `Content-Type: application/json`.
- JSON parsing errors in frontend: wrapped `JSON.parse` in `try/catch` and added guards before using DOM elements (scrollTo/focus).
- Missing images / 404s: moved the stray file to a backup, created images directory in Apache htdocs, copied `male.jpg` in, set proper ownership/perms.
- “Not a directory” error: detected `images` was a regular file, moved it, then recreated the directory and restored the file inside it.
- Signup datetime: changed to `date("Y-m-d H:i:s")`.
- Deprecation: initialized `$data = []` instead of `false` in include files.
- Sessions in curl: used `--cookie-jar` and `--cookie` to persist the PHPSESSID across requests.

Files I changed (most important)

- `api.php` — disable displayed errors, start session early, set JSON header
- `index.html` — safer JSON parsing & DOM guards
- `classes/database.php` — catch DB execute errors
- `includes/signup.php` — fixed date format
- `includes/*.php` — changed `$data = false` → `$data = []`
- Copied image: `male.jpg` → `ui/images/male.jpg`

Most useful commands I ran

Check the image served:
```bash
curl -I http://127.0.0.1/carochat/ui/images/male.jpg
```

Login + save cookies, then request user_info:
```bash
printf '%s' '{"data_type":"login","email":"eathorne@yahoo.com","password":"password"}' \
  | curl -s -X POST -H "Content-Type: application/json" --cookie-jar cookies.txt --data @- http://127.0.0.1/carochat/api.php -D -

printf '%s' '{"data_type":"user_info"}' \
  | curl -s -X POST -H "Content-Type: application/json" --cookie cookies.txt --data @- http://127.0.0.1/carochat/api.php -D -
```

Fix the weird images file (what I did):
```bash
sudo mv /Applications/XAMPP/htdocs/carochat/ui/images /Applications/XAMPP/htdocs/carochat/ui/images.backup
sudo mkdir -p /Applications/XAMPP/htdocs/carochat/ui/images
sudo mv /Applications/XAMPP/htdocs/carochat/ui/images.backup /Applications/XAMPP/htdocs/carochat/ui/images/male.jpg
sudo cp /Users/admin/Desktop/Projects/Carochat/UI/images/male.jpg /Applications/XAMPP/htdocs/carochat/ui/images/
sudo chown -R daemon:daemon /Applications/XAMPP/htdocs/carochat/ui
sudo chmod -R 755 /Applications/XAMPP/htdocs/carochat/ui
```

Commit the image to the repo:
```bash
git add UI/images/male.jpg
git commit -m "Add UI image: male.jpg"
git push origin main
```

Restart Apache
```bash
sudo /Applications/XAMPP/xamppfiles/bin/apachectl restart
```

Repository icons checks
```bash
# repo icons
ls -la /Users/admin/Desktop/Projects/Carochat/UI/icons

# files currently served by Apache
ls -la /Applications/XAMPP/htdocs/carochat/ui/icons

sudo cp /Applications/XAMPP/htdocs/carochat/ui/icons/chat.png /Applications/XAMPP/htdocs/carochat/ui/icons/attach.png
sudo chown daemon:daemon /Applications/XAMPP/htdocs/carochat/ui/icons/attach.png
sudo chmod 644 /Applications/XAMPP/htdocs/carochat/ui/icons/attach.png
```

Recent debugging & resolutions (follow‑up notes)

1. Enter key not sending messages reliably

- Problem: Pressing Enter didn't submit the message in some cases. The dynamically-inserted input used `onkeyup` and the handler relied on `keyCode`, which can behave inconsistently across browsers and dynamic elements.
- Fix: Updated `index.html` to use a robust `enter_pressed(e)` handler that checks `e.key`, `e.keyCode` and `e.which`, prevents default, and calls `send_message`. Also changed inline attribute to `onkeydown='enter_pressed(event)'` in `api.php`'s `message_controls()` output and cleared/refocused the input after sending.

2. `file_exists()` deprecation warnings

- Problem: PHP 8.2 warned when `file_exists()` was called with `null` (e.g. `file_exists($row->image)` when `image` was null).
- Fix: Guarded checks with `!empty($row->image) && file_exists($row->image)` in `includes/contacts.php`, `includes/chats.php`, `includes/send_messages.php`, and helper functions in `api.php`.

3. Apache serving stale copy / htdocs mismatch

- Problem: Edits in workspace didn't show up because Apache served from `/Applications/XAMPP/htdocs/carochat` — rsync and ownership fixes were required.
- Fix: Used `sudo rsync -av --delete --exclude='.git' --exclude='.DS_Store'` to sync workspace to htdocs, `chown -R daemon:daemon`, `chmod` directories/files appropriately, and restarted XAMPP. Avoid copying `.git` into htdocs; exclude it.

4. Missing `messages` table

- Problem: Chat UI had no data because the `messages` table didn't exist in `carochat_db` (only `users` existed).
- Fix: Created `messages` table with the expected columns (id, sender, receiver, message, files, date, msgid, received, seen, deleted_sender, deleted_receiver). Verified table exists and row count.

5. Backwards compatibility of avatar filenames

- Problem: Some code referenced `ui/images/user_male.jpg` while others used `ui/images/male.jpg`, causing 404s for older DB rows or message files.
- Fix: Normalized defaults in `api.php` to `ui/images/male.jpg` / `ui/images/girl.jpg`. Options: either update DB rows to the new paths or create copies (e.g. `user_male.jpg`) in htdocs for compatibility.

Commands for DB fixes (if there are legacy paths stored in DB):
```bash
# find messages / users referencing the old name
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "USE carochat_db; SELECT id, sender, receiver, files FROM messages WHERE files LIKE '%user_male.jpg%';"
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "USE carochat_db; SELECT id, userid, username, image FROM users WHERE image LIKE '%user_male.jpg%';"

# replace old paths with new ones
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "USE carochat_db; UPDATE messages SET files = REPLACE(files,'ui/images/user_male.jpg','ui/images/male.jpg') WHERE files LIKE '%user_male.jpg%';"
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "USE carochat_db; UPDATE users SET image = REPLACE(image,'ui/images/user_male.jpg','ui/images/male.jpg') WHERE image LIKE '%user_male.jpg%';"
```


End of diary entry.

---

2026-03-21 — peers UI removal & deployment notes

- Removed the right-hand peers UI from `index.html` and suppressed the peers-list rendering; the runtime still updates `window.signalingClient.peers` so call logic (throttling, target-unavailable checks) remains functional.
- Performed a cautious deployment from the workspace to XAMPP htdocs:
  - Created a timestamped tar.gz backup under `/Applications/XAMPP/htdocs/carochat-backups/` before syncing.
  - Ran `rsync` (dry-run then real) excluding `.git`, `node_modules`, `.vscode`, and preserving the existing `uploads/` directory to avoid data loss.
  - Fixed permissions and ownership: ensured `/Applications/XAMPP/htdocs/carochat/uploads` exists and chowned the deployed tree to the Apache user (`_www`), adjusted file/dir modes.
  - Restarted Apache (XAMPP) and restarted the signaling server using the deployed `signaling-server.js`; logs are sent to `/tmp/carochat-signaling.log`.

- Quick verification:
  - Backup path: `/Applications/XAMPP/htdocs/carochat-backups/carochat-backup-<TIMESTAMP>.tar.gz`
  - Signaling server confirmed: "Signaling server starting on ws://localhost:3000" and "Signaling server ready" in `/tmp/carochat-signaling.log`.
  - I can tail the signaling log while you perform a hold-to-talk test from one browser to another to confirm offer/answer/candidate flow.

Next small cleanups (optional):
- Remove or comment out the now-unused `renderPeersUI()` function and any orphaned CSS for the peers panel.
- Commit this diary update (I can run the git add/commit/push if you want).

---

2026-03-22 — uploads, left-panel flicker, server hardening, and mobile UX

Today I focused on a group of related problems that surfaced while testing media uploads and the chat-list UX:

Key outcomes
- Fixed failing uploads by correcting filesystem ownership and hardening the server-side upload handler.
- Hardened the client `fetchChats()` flow and added protections so a transient/empty server response doesn't wipe the freshly-rendered chat list (reduced flicker).
- Added a small persistent "hide" control inside `#inner_left_panel` so mobile users can collapse the chat list and let messages take full width.
- Adjusted the deployment script so `uploads/` ownership is preserved and not overwritten during deploy.

Details (what I changed & why)
- Permissions & uploads
  - Discovered `move_uploaded_file()` was failing with "Permission denied" in `uploader.php` because Apache child processes run as `daemon` but the `uploads/` folder (and earlier copies) had different ownership.
  - Fixed on-disk ownership: `chown daemon:daemon /Applications/XAMPP/htdocs/carochat/uploads` and set mode 755 for directories (this allowed `move_uploaded_file` to succeed).
  - Tested with curl before/after the fix — uploads returned HTTP 500 (before) and HTTP 200 with JSON and the `file` path (after).

- Server hardening (`uploader.php`)
  - Added logging for `error_get_last()` on move failures and return a short `reason` in JSON for easier debugging.
  - Guarded DB insert paths so missing `userid`/`sender` do not cause integrity errors or uncaught PHP warnings.
  - Ensured successful uploads return the `file` field in JSON so the client can update the UI immediately.

- Deployment script (`scripts/deploy_ui.sh`)
  - Set `APACHE_USER="daemon"` to match the running Apache worker user on macOS XAMPP.
  - Changed `chown` to apply only to `$DEST_DIR/uploads` (instead of the full deploy) to avoid flipping repository file ownership during deploys.

- Client-side fixes (`index.html`)
  - Reworked `fetchChats()` to call the JSON POST API (`api.php` with `{data_type:'chats'}`) instead of GET `api.php?action=get_chats` (the GET path intermittently returned empty bodies that triggered client retries and UI fallbacks).
  - Implemented retries + backoff and a cached last-successful chat payload so the client can fall back when the API is flaky.
  - Added timestamp protection (`window.__lastLeftUpdate`) and content-inspection helpers (`htmlContainsChats()` / `shouldApplyLeftUpdate()`) so server-initiated left-panel updates don't overwrite a user-triggered render for a short window.
  - Removed a duplicate click handler that previously issued a GET to `api.php?action=get_chats` and caused races.

- Mobile UX — hide button
  - Added a small `hide` / `show` button in `#inner_left_panel` (CSS + JS) that toggles `body.left-hidden` on mobile (<= 500px). When hidden, the messages panel expands to the full width.
  - Ensured the button persists after server-driven HTML replacements by attaching a MutationObserver that re-adds the button when `#inner_left_panel`'s children are replaced.

What I verified
- curl uploads: POST multipart to `uploader.php` now returns HTTP 200 and JSON: {"message":"Your file was uploaded","data_type":"send_image","file":"uploads/<name>"}.
- Manual UI testing: the hide button is injected and toggles the left-panel visibility in small viewports. `fetchChats()` now POSTs JSON; no stray GET calls to `api.php?action=get_chats` should occur.

Next recommended actions
- Add a tiny server-side debug line in `api.php` (get_chats handler) that logs request method, session userid, and response length when the endpoint is called. This will help fully diagnose why empty responses occurred intermittently; I can add that if you want.
- Persist the hide/show preference in `localStorage` so the collapsed state survives reloads (low-risk UX improvement).
- Optionally change the hide button to a small icon and animate the transition (polish).

Files touched (high level)
- `uploader.php` — improved logging, return `file`, guarded DB writes.
- `index.html` — fetchChats POST conversion, caching/retries, left-panel overwrite guard, hide button + MutationObserver, removed duplicate click handler.
- `scripts/deploy_ui.sh` — changed APACHE_USER and more conservative chown.

If you'd like I can commit this diary update to git for you (run `git add DEV_DIARY.md && git commit -m "DEV: update diary with uploads & left-panel fixes" && git push`).


Latest changes (added during this session)

- Deployed edits and UX fixes:
  - Hardened Enter-key handling and fixed `message_controls()` to use `onkeydown` so messages send reliably on Enter.
  - Cleared and refocused the message input after sending.
  - Hardened JSON parsing in `index.html` to avoid crashes when the server returns HTML.

- Uploads:
  - Updated `uploader.php` to accept `jpg`, `jpeg`, and `png` uploads. Added MIME and extension checks and deployed to htdocs.
  - Updated client-side `index.html` checks for `upload_profile_image()` and `send_image()` to allow `jpg`, `jpeg`, `png` (case-insensitive).
  - Deployed `uploader.php` and `index.html` to `/Applications/XAMPP/htdocs/carochat` and set owner/permissions.

- Images & icons:
  - Copied `male.jpg`, `girl.jpg` and `trash.png` into the Apache-served `ui/images` and `ui/icons` folders and set `daemon:daemon` ownership with 644/755 perms as appropriate.
  - Noted path-casing differences (`UI/` vs `ui/`) and kept both in sync where necessary.

- Database:
  - Created `messages` table (id, sender, receiver, message, files, date, msgid, received, seen, deleted_sender, deleted_receiver) because the app expected it; verified row count is 0.
  - Provided DB queries to normalize legacy avatar path values (e.g. `user_male.jpg` → `male.jpg`) and to search for legacy references.

- Deployment & permissions:
  - Used `sudo rsync -av --delete --exclude='.git' --exclude='.DS_Store'` to sync the workspace to XAMPP htdocs.
  - Set ownership with `sudo chown -R daemon:daemon /Applications/XAMPP/htdocs/carochat` and fixed permissions with `find`/`chmod` (directories 755, files 644).
  - Restarted XAMPP (Apache + MySQL) and confirmed services started.

- Misc fixes:
  - Guarded `file_exists()` calls with `!empty(...)` to avoid PHP 8.2 deprecation warnings.
  - Wrapped DB execute calls in try/catch in `classes/database.php` to avoid uncaught exceptions.
  - Fixed signup date format to `Y-m-d H:i:s`.

Verification & smoke tests you can run

1. Reload application in Arc: http://127.0.0.1/carochat/index.html (use hard refresh)
2. Login as an existing user (e.g. `eathorne@yahoo.com` / `password`) and:
   - Click a contact and send a text message by pressing Enter.
   - Attach and send a `.jpg`/`.jpeg`/`.png` image from the chat UI.
   - Change profile image to a supported type and verify it updates.
3. Use curl to verify API responses and session behavior (examples in the diary above).

Commit guidance (if you want this in git)

```bash
git add DEV_DIARY.md api.php index.html uploader.php
git commit -m "DEV: add session diary and recent fixes (uploads, enter-key, messages table, deploy)"
git push origin main
```

If you'd like, I can run the commit & push step for you (pick option C from the previous prompt), or I can run the DB normalization or create compatibility copies for avatars. Otherwise this diary now includes everything we did.

---

## Walkie-talkie feature: voicenotes → live walkie (high-level notes)

Background and intent
- The goal was to add a simple walkie-talkie (push-to-talk / hold-to-talk) audio feature so a user could transmit voice to a peer and the peer would hear automatically without requiring the peer to grant microphone access unless they want to transmit back.

Design decisions
- One-way receive: callee should be able to hear without `getUserMedia`; answers are created without adding local tracks. This avoids forcing an audio permission prompt on the listener.
- Keep the peer connection alive: when the sender releases the walkie button, mute local tracks (`track.enabled = false`) instead of closing the `RTCPeerConnection` so the receive path stays valid for the next transmission.
- Minimal signaling: keep a simple forwarder server (WebSocket) mapping `clientId -> socket`; messages are forwarded with explicit `from` and `to` fields.
- Autoplay workaround: use an `AudioContext` resume + tiny silent buffer to unlock playback, and expose a persistent "Enable sound" button if the browser blocks autoplay.

Files changed (high level)
- `index.html`
  - Added signaling client code (WebSocket connect/register/send/receive).
  - Implemented `RTCPeerConnection` handling, remote audio playback, and incoming-audio indicators.
  - Added `startLocalStream`, `stopLiveTalk()`, and `stopTransmit()` so transmission can stop without tearing down the whole connection.
  - Added negotiation guards, peer throttling, and safer ID normalization.
  - Exposed debug helpers on `window` for repeatable testing.
- `signaling-server.js` / `signaling/signaling-server.js`
  - Added the signaling forwarder used by the live-talk flow.

---

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

---

2026-03-22 14:30 — Make contact rows open chat on click

- Updated `includes/contacts.php` to render each contact row with a `userid` attribute and `onclick="start_chat(event)"` so selecting a contact opens the chat in the inner right panel.
- Hardened `start_chat` in `index.html` to use `e.currentTarget` (with fallbacks) so clicks on child elements (image, text, buttons) correctly resolve the target user id and open the chat.

How to test:
- Open the app locally, go to Contacts, click any contact. The chat should open in the right panel and messages load.
- If chat does not load, inspect DevTools → Network for the `api.php` call and Console for JSON/HTML errors (session/login redirects may cause HTML responses).

Notes:
- This is a low-risk change that only modifies server-rendered contact row HTML and client-side click handling. No new API endpoints were added.

---

2026-03-31 — mobile chat refresh, contacts/settings cleanup, and composer polish

Summary

I went through the current workspace diff after the 2026-03-22 notes and captured a new round of UI cleanup focused on mobile chat flow, clearer contact/settings markup, and a more polished message composer.

Main outcomes

- Reworked the small-screen experience into a proper single-panel mobile chat flow instead of trying to squeeze the desktop split layout onto narrow screens.
- Cleaned up the contacts and settings payload markup so both screens are safer to render and easier to style consistently.
- Refreshed the chat composer and thread actions so file/audio/live controls are clearer in both the desktop and mobile thread views.

What changed

1. Mobile chat flow (`index.html`)

- Replaced the old mobile behavior with a full-height single-panel layout that uses `#inner_left_panel` as the active mobile surface.
- Added helpers to transform server-rendered chat HTML into a mobile chat list and thread view:
  - quick-access avatars
  - recent conversation rows
  - empty-state copy
  - a mobile thread header with avatar/title
  - a back button that returns to the right source view
- Added `__mobileChatReturnView` and forced-left-update handling so moving between Chat, Contacts, and Settings on mobile does not get immediately overwritten by async panel refreshes.
- Kept desktop behavior intact: desktop still renders chats in the split left/right layout, while mobile now renders threads directly inside the left panel.

2. Composer and thread actions (`api.php`, `index.html`)

- Updated `message_controls()` in `api.php` to emit reusable classes for:
  - the delete-thread action
  - the composer container
  - attach/audio/live chips
  - the icon-based send button
- Matched that new markup with fresh CSS in `index.html` so the composer works visually inside the new mobile thread shell as well as the existing desktop thread layout.
- Kept the Enter-to-send path in place and aligned the composer markup with the current audio/live controls.

3. Contacts screen cleanup (`includes/contacts.php`, `index.html`)

- Added explicit classes for contact rows, avatars, metadata, actions, badges, the search input, the list shell, and empty states so the contacts screen is easier to target from CSS/JS.
- Kept contact rows clickable for chat start, while still allowing Add/Delete buttons to stop propagation cleanly.
- Changed blank searches to fall back to the default contacts view instead of leaving the UI in an empty search state.
- Added mobile-specific contacts styling hooks via body classes so the contacts panel can be presented cleanly in the new mobile layout.

4. Settings screen cleanup (`includes/settings.php`, `index.html`)

- Escaped username, email, and password values with `htmlspecialchars(...)` before rendering them back into the settings form.
- Switched the settings response to return `data_type = "settings"` instead of `"contacts"`, which matches the actual panel being rendered.
- Refactored the settings HTML into class-based markup (`settings_shell`, `settings_form`, `settings_input`, etc.) so the page is easier to style and reason about.
- Fixed the save button label reset to "Save Settings" after the request completes.
- Added body-level `settings-open` / `contacts-open` state handling in `index.html` so mobile-specific styling can follow the active tab reliably.

5. Testing notes

- Generated additional upload fixtures in `uploads/` while checking image/audio flows and mobile-thread behavior.
- This diary entry is based on the current workspace changes in:
  - `api.php`
  - `includes/contacts.php`
  - `includes/settings.php`
  - `index.html`

Files touched (high level)

- `index.html` — mobile chat list/thread rendering, body-state syncing, mobile CSS, composer styling, and tab-return behavior.
- `api.php` — composer markup refresh for files/audio/live/send actions.
- `includes/contacts.php` — better contact-row markup, empty states, and search fallback behavior.
- `includes/settings.php` — escaped values, corrected response type, and cleaner settings markup.

---

2026-04-01 — mobile flash debugging, stale-response guards, and chat-open loading state

Summary

I spent this session tracking down the remaining mobile layout flashes that still appeared when moving from contacts/settings/search into a chat thread and when returning from mobile chat views.

What I found

- The earlier console spam (`Ignoring stale left-panel response ...`) confirmed that older left-panel requests were still arriving out of order, but those stale responses were mostly a symptom rather than the final visual cause.
- The real visual flash came from mobile transitions briefly leaving old DOM on screen:
  - an older `settings` / `contacts` / `search` response could still be in flight while the UI had already moved on
  - the back/reset path removed mobile thread classes before replacement content arrived, so the old thread DOM briefly fell back to older layout rules
  - clicking a mobile contact/search result left the search results visible until the chat request completed
- The body-state monitor also treated some left-panel mobile threads as "closed", which could leave `messages-closed` applied at the wrong moment.

What I changed (`index.html`)

1. Guarded left-panel responses

- Added request metadata for left-panel requests (`contacts`, `chats`, `settings`) and ignore stale responses if a newer request has already been issued.
- Added an active-view check so a response for an inactive tab does not repaint the left panel after the user has already switched elsewhere.
- Restricted settings refreshes after `save_settings` and profile-image updates so `get_settings(true)` only runs if Settings is still the active view.

2. Fixed mobile thread/body-state transitions

- Updated the mobile messages-state monitor so a left-panel mobile thread counts as an open chat, not just right-panel message markup.
- Cleared `left-hidden` when a chat is actively open.
- Made `render_mobile_chat_list()` and `render_mobile_chat_thread()` set `messages-open` / `messages-closed` immediately instead of waiting for the observer to catch up.

3. Removed the old-DOM flash during back/navigation

- Updated `reset_mobile_chat_panel()` so mobile clears the old `#inner_left_panel` HTML immediately when leaving a thread/list state.
- This prevents the previous chat/search/settings markup from sitting on screen for one request cycle and falling back to the older mobile/desktop CSS rules.

4. Added an explicit mobile chat-opening state

- Added `render_mobile_chat_loading()` so tapping a mobile contact/search result immediately swaps the old results pane out for a neutral "Opening chat" card while the `chats` request is in flight.
- Wired `start_chat()` to use that loading state on mobile before requesting the thread payload.
- Added CSS for the temporary loading card inside the mobile thread shell so the UI stays visually consistent during the transition.

5. Reduced console noise

- Gated the left-panel debug logs behind `window.CAROCHAT_DEBUG_LEFT_PANEL` so normal use does not flood DevTools with stale-response and polling messages.
- The guard logs can still be re-enabled when needed for debugging future race conditions.

How I verified it

- Reproduced the issue in Chrome DevTools mobile emulation (`iPhone SE`) while switching between Contacts, Settings, search results, and chat threads.
- Used the console output to confirm the stale-response guard was firing and to distinguish between real repaint problems and harmless old requests arriving late.
- Focused specifically on the search-result click path, since that was still showing the older mobile search layout after an item was selected.

Files touched

- `index.html` — stale-response guards, active-view checks, body-state fixes, mobile reset cleanup, chat-opening loading state, and debug-log gating.

<?php
// Simple shim: redirect requests for index.php to index.html
// The project ships with index.html as the main UI. Some flows (login) redirect to index.php,
// so this file ensures a 404 won't occur and the user lands on the UI.
header('Location: index.html');
exit;

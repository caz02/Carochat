<?php

define ('DBUSER', "root");
define ('DBPASS', "");
define ('DBNAME', "carochat_db");
// prefer TCP loopback to avoid socket path mismatches between different PHP builds
// set to '127.0.0.1' to force TCP; if you run a socket-only setup, change to 'localhost'
define ('DBHOST', "127.0.0.1");

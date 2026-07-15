<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/security.php';

admin_session_boot();
session_unset();
session_destroy();
header('Location: login.php');
exit;

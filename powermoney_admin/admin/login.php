<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';

send_security_headers();
admin_session_boot();

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$ip = client_ip();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $error = '세션이 만료되었습니다. 다시 시도해 주세요.';
    } elseif (ip_is_blocked($ip)) {
        $error = '로그인 시도가 너무 많습니다. 30분 후 다시 시도해 주세요.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $st = db()->prepare('SELECT * FROM pm_admin WHERE username = ?');
        $st->execute([$username]);
        $admin = $st->fetch();

        if ($admin && password_verify($password, $admin['pass_hash'])) {
            session_regenerate_id(true); // 세션 고정 공격 방지
            $_SESSION['admin_id']   = (int)$admin['id'];
            $_SESSION['admin_name'] = $admin['username'];
            $_SESSION['last_seen']  = time();

            $st = db()->prepare('UPDATE pm_admin SET last_login = ? WHERE id = ?');
            $st->execute([date('Y-m-d H:i:s'), $admin['id']]);

            header('Location: index.php');
            exit;
        }

        // 실패 기록 — 5분 내 5회 초과 시 30분 차단
        if (!rate_hit($ip, 'login', LOGIN_FAIL_LIMIT)) {
            $error = '로그인 시도가 너무 많습니다. 30분 후 다시 시도해 주세요.';
        } else {
            $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h(SITE_NAME) ?> 관리자 로그인</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="auth-body">
<div class="auth-card">
  <div class="auth-logo">POWER <b>MONEYPLAN</b></div>
  <h1>관리자 로그인</h1>
  <?php if ($error): ?><p class="auth-error"><?= h($error) ?></p><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label>아이디
      <input type="text" name="username" required maxlength="30" autofocus>
    </label>
    <label>비밀번호
      <input type="password" name="password" required maxlength="100">
    </label>
    <button type="submit">로그인</button>
  </form>
</div>
</body>
</html>

<?php
declare(strict_types=1);

/* =====================================================
 *  최초 관리자 계정 생성 (1회용)
 *  - config.php 의 SETUP_KEY 를 알아야만 접근 가능
 *  - 관리자 계정이 이미 있으면 무조건 거부
 *  - 계정 생성 후 이 파일은 서버에서 삭제 권장
 * ===================================================== */

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';

send_security_headers();
admin_session_boot();

$st = db()->query('SELECT COUNT(*) FROM pm_admin');
if ((int)$st->fetchColumn() > 0) {
    http_response_code(403);
    exit('이미 관리자 계정이 존재합니다. 이 파일(setup.php)을 삭제하세요.');
}

$error = $done = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $key      = $_POST['setup_key'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!hash_equals(SETUP_KEY, $key)) {
        $error = '설치 키가 올바르지 않습니다.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,30}$/', $username)) {
        $error = '아이디는 영문/숫자/밑줄 4~30자로 입력하세요.';
    } elseif (strlen($password) < 10) {
        $error = '비밀번호는 10자 이상으로 입력하세요.';
    } else {
        $st = db()->prepare('INSERT INTO pm_admin (username, pass_hash, created_at) VALUES (?, ?, ?)');
        $st->execute([$username, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
        $done = '관리자 계정이 생성되었습니다. 보안을 위해 이 파일(setup.php)을 삭제한 뒤 로그인하세요.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>관리자 계정 생성</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="auth-body">
<div class="auth-card">
  <div class="auth-logo">POWER <b>MONEYPLAN</b></div>
  <h1>최초 관리자 계정 생성</h1>
  <?php if ($error): ?><p class="auth-error"><?= h($error) ?></p><?php endif; ?>
  <?php if ($done): ?>
    <p class="auth-ok"><?= h($done) ?></p>
    <p style="text-align:center"><a href="login.php">로그인 하러 가기 →</a></p>
  <?php else: ?>
  <form method="post" autocomplete="off">
    <label>설치 키 (config.php 의 SETUP_KEY)
      <input type="password" name="setup_key" required>
    </label>
    <label>관리자 아이디
      <input type="text" name="username" required maxlength="30" pattern="[a-zA-Z0-9_]{4,30}">
    </label>
    <label>비밀번호 (10자 이상)
      <input type="password" name="password" required minlength="10" maxlength="100">
    </label>
    <button type="submit">계정 생성</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>

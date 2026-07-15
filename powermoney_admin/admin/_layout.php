<?php
declare(strict_types=1);

/* 어드민 공용 레이아웃 — 상단바 + 접이식 사이드바 */

require_once __DIR__ . '/../lib/helpers.php';

function admin_page_start(string $title, string $active): void
{
    $menu = [
        '서비스신청' => [
            ['key' => 'apply', 'label' => '신청문의', 'href' => 'index.php', 'icon' => '📋'],
        ],
        '통계' => [
            ['key' => 'visit', 'label' => '접속자 로그', 'href' => 'visit.php', 'icon' => '📈'],
        ],
    ];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?></title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<script>/* 사이드바 접힘 상태 복원 (화면 깜빡임 방지를 위해 최상단에서 실행) */
if (localStorage.getItem('pm_side') === '1') document.body.classList.add('side-collapsed');
</script>

<header class="topbar">
  <div class="topbar-in">
    <div class="top-left">
      <button type="button" class="side-toggle" onclick="toggleSide()" title="메뉴 접기/펴기" aria-label="메뉴 접기/펴기">☰</button>
      <div class="brand">POWER <b>MONEYPLAN</b> <span>ADMIN</span></div>
    </div>
    <div class="top-right">
      <a class="btn btn-home" href="<?= h(SITE_URL) ?>" target="_blank">🏠 홈페이지</a>
      <span class="admin-name"><?= h($_SESSION['admin_name'] ?? '') ?> 님</span>
      <a class="btn btn-ghost" href="logout.php">로그아웃</a>
    </div>
  </div>
</header>

<div class="layout">
  <aside class="sidebar">
    <nav>
      <?php foreach ($menu as $group => $items): ?>
        <div class="side-group"><?= h($group) ?></div>
        <?php foreach ($items as $m): ?>
          <a class="side-link <?= $m['key'] === $active ? 'on' : '' ?>" href="<?= h($m['href']) ?>">
            <span class="side-icon"><?= $m['icon'] ?></span><?= h($m['label']) ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="content">
<?php
}

function admin_page_end(): void
{
?>
  </div><!-- /.content -->
</div><!-- /.layout -->
<script>
function toggleSide() {
  document.body.classList.toggle('side-collapsed');
  localStorage.setItem('pm_side', document.body.classList.contains('side-collapsed') ? '1' : '0');
}
</script>
</body>
</html>
<?php
}

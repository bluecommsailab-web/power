<?php
declare(strict_types=1);

/* 접속자 로그 (그누보드 접속자집계 참조) — 기간 검색 + 유입경로/브라우저/OS/기기 */

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/_layout.php';

send_security_headers();
admin_session_boot();
admin_require_login();

$pdo = db();

/* ---------- 기간 로그 삭제 (POST) ---------- */
$flash = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['act'] ?? '') === 'del_range') {
    csrf_verify_or_die();
    $ds = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['del_sdate'] ?? '') ? $_POST['del_sdate'] : '';
    $de = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['del_edate'] ?? '') ? $_POST['del_edate'] : '';
    if ($ds !== '' && $de !== '') {
        $st = $pdo->prepare('DELETE FROM pm_visit WHERE created_at >= ? AND created_at <= ?');
        $st->execute([$ds . ' 00:00:00', $de . ' 23:59:59']);
        $flash = '로그 ' . $st->rowCount() . '건이 삭제되었습니다.';
    }
}

/* ---------- 필터 (기본: 오늘) ---------- */
if (!empty($_GET['quick'])) {
    [$qs, $qe] = quick_range($_GET['quick']);
    $_GET['sdate'] = $qs;
    $_GET['edate'] = $qe;
} elseif (!isset($_GET['sdate']) && !isset($_GET['edate'])) {
    $_GET['sdate'] = $_GET['edate'] = date('Y-m-d');
}
$sdate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['sdate'] ?? '') ? $_GET['sdate'] : '';
$edate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['edate'] ?? '') ? $_GET['edate'] : '';

$where  = [];
$params = [];
if ($sdate !== '') {
    $where[]  = 'created_at >= ?';
    $params[] = $sdate . ' 00:00:00';
}
if ($edate !== '') {
    $where[]  = 'created_at <= ?';
    $params[] = $edate . ' 23:59:59';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ---------- 페이지네이션 ---------- */
$perPage = 30;
$page    = max(1, (int)($_GET['page'] ?? 1));

$st = $pdo->prepare("SELECT COUNT(*) FROM pm_visit $whereSql");
$st->execute($params);
$total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);

$offset = ($page - 1) * $perPage;
$st = $pdo->prepare("SELECT * FROM pm_visit $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll();

/* ---------- 요약 ---------- */
$st = $pdo->prepare('SELECT COUNT(*) FROM pm_visit WHERE created_at >= ?');
$st->execute([date('Y-m-d') . ' 00:00:00']);
$statToday = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM pm_visit $whereSql" . ($whereSql ? ' AND' : ' WHERE') . " keyword != ''");
$st->execute($params);
$statSearch = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM pm_visit $whereSql" . ($whereSql ? ' AND' : ' WHERE') . " device = '모바일'");
$st->execute($params);
$statMobile = (int)$st->fetchColumn();

$qsBase = http_build_query(array_filter(['sdate' => $sdate, 'edate' => $edate]));
$csrf   = csrf_token();

admin_page_start(SITE_NAME . ' 접속자 로그', 'visit');
?>
<main class="wrap">

  <div class="page-head">
    <h1>접속자 로그</h1>
    <form method="post" onsubmit="return confirm('조회 기간(<?= h($sdate ?: '전체') ?> ~ <?= h($edate ?: '전체') ?>)의 접속 로그를 모두 삭제하시겠습니까?')">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="act" value="del_range">
      <input type="hidden" name="del_sdate" value="<?= h($sdate) ?>">
      <input type="hidden" name="del_edate" value="<?= h($edate) ?>">
      <button type="submit" class="btn btn-red" <?= ($sdate === '' || $edate === '') ? 'disabled title="기간을 지정해야 삭제할 수 있습니다."' : '' ?>>조회 기간 로그 삭제</button>
    </form>
  </div>

  <?php if ($flash): ?><p class="flash-ok"><?= h($flash) ?></p><?php endif; ?>

  <div class="stat-row stat-row-3">
    <div class="stat"><span>오늘 접속자</span><b><?= number_format($statToday) ?></b></div>
    <div class="stat"><span>조회 기간 접속</span><b><?= number_format($total) ?></b></div>
    <div class="stat"><span>검색 유입 / 모바일</span><b><?= number_format($statSearch) ?> <small>/</small> <?= number_format($statMobile) ?></b></div>
  </div>

  <form class="filter" method="get">
    <div class="filter-row">
      <label class="fl">기간별검색</label>
      <input type="date" name="sdate" value="<?= h($sdate) ?>"> ~
      <input type="date" name="edate" value="<?= h($edate) ?>">
      <button type="submit" class="btn btn-pink">검색</button>
      <span class="quick">
        <?php foreach (['today' => '오늘', 'yesterday' => '어제', 'this_week' => '이번주', 'this_month' => '이번달', 'last_week' => '지난주', 'last_month' => '지난달', '' => '전체'] as $qk => $ql): ?>
          <a class="qbtn" href="?quick=<?= h($qk) ?><?= $qk === '' ? '&sdate=&edate=' : '' ?>"><?= h($ql) ?></a>
        <?php endforeach; ?>
      </span>
    </div>
  </form>

  <div class="table-meta">총 <b><?= number_format($total) ?></b>건</div>

  <div class="table-scroll">
  <table class="board">
    <thead>
      <tr>
        <th>번호</th>
        <th>IP</th>
        <th>접속 경로</th>
        <th>채널</th>
        <th>브라우저</th>
        <th>OS</th>
        <th>접속기기</th>
        <th>일시</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="8" class="empty">조회 기간에 접속 기록이 없습니다.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="dim"><?= (int)$r['id'] ?></td>
        <td><?= h($r['ip']) ?></td>
        <td class="path-cell">
          <?php if ($r['keyword'] !== ''): ?>
            <span class="kw-label"><?= h(keyword_label($r)) ?></span>
          <?php elseif ($r['referrer'] !== ''): ?>
            <span class="ref-url" title="<?= h($r['referrer']) ?>"><?= h(mb_substr($r['referrer'], 0, 80)) ?></span>
          <?php else: ?>
            <span class="dim">직접유입</span>
          <?php endif; ?>
        </td>
        <td><span class="ch-badge"><?= h($r['channel']) ?></span></td>
        <td><?= h($r['browser']) ?></td>
        <td><?= h($r['os']) ?></td>
        <td><?= h($r['device']) ?></td>
        <td class="dim"><?= h($r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <nav class="paging">
    <?php
    $from = max(1, $page - 4);
    $to   = min($pages, $page + 4);
    if ($page > 1): ?>
      <a href="?<?= h($qsBase) ?>&page=<?= $page - 1 ?>">‹</a>
    <?php endif;
    for ($i = $from; $i <= $to; $i++): ?>
      <a href="?<?= h($qsBase) ?>&page=<?= $i ?>" class="<?= $i === $page ? 'on' : '' ?>"><?= $i ?></a>
    <?php endfor;
    if ($page < $pages): ?>
      <a href="?<?= h($qsBase) ?>&page=<?= $page + 1 ?>">›</a>
    <?php endif; ?>
  </nav>

</main>
<?php admin_page_end(); ?>

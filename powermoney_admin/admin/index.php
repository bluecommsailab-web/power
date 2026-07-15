<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/_layout.php';

send_security_headers();
admin_session_boot();
admin_require_login();

$pdo = db();

/* ---------- 필터 ---------- */
if (!empty($_GET['quick'])) {
    [$qs, $qe] = quick_range($_GET['quick']);
    $_GET['sdate'] = $qs;
    $_GET['edate'] = $qe;
}
$filter = build_apply_filter($_GET);
$f      = $filter['f'];

/* ---------- 페이지네이션 ---------- */
$perPage = (int)($_GET['pp'] ?? 20);
if (!in_array($perPage, [20, 50, 100], true)) {
    $perPage = 20;
}
$page    = max(1, (int)($_GET['page'] ?? 1));

$st = $pdo->prepare("SELECT COUNT(*) FROM pm_apply {$filter['where']}");
$st->execute($filter['params']);
$total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);

$offset = ($page - 1) * $perPage;
$st = $pdo->prepare("SELECT * FROM pm_apply {$filter['where']} ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$st->execute($filter['params']);
$rows = $st->fetchAll();

/* ---------- 요약 통계 ---------- */
$today = date('Y-m-d');
$stats = [];
$st = $pdo->prepare("SELECT COUNT(*) FROM pm_apply WHERE created_at >= ?");
$st->execute([$today . ' 00:00:00']);
$stats['today'] = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM pm_apply WHERE created_at >= ?");
$st->execute([date('Y-m-d', strtotime('monday this week')) . ' 00:00:00']);
$stats['week'] = (int)$st->fetchColumn();

$stats['all'] = (int)$pdo->query("SELECT COUNT(*) FROM pm_apply")->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM pm_apply WHERE status = ?");
$st->execute(['접수중']);
$stats['new'] = (int)$st->fetchColumn();

/* 필터 유지용 쿼리스트링 */
$qsBase = http_build_query(array_filter([
    'sdate' => $f['sdate'], 'edate' => $f['edate'], 'status' => $f['status'],
    'sfield' => $f['sword'] !== '' ? $f['sfield'] : '', 'sword' => $f['sword'], 'pp' => $perPage,
]));
$csrf = csrf_token();

admin_page_start(SITE_NAME . ' 신청문의 관리', 'apply');
?>
<main class="wrap">

  <div class="page-head">
    <h1><?= h(SITE_NAME) ?> 신청문의</h1>
    <div class="bulk-btns">
      <button type="button" class="btn btn-blue"  onclick="bulkAction('bulk_status','접수완료')">선택 접수완료 처리</button>
      <button type="button" class="btn btn-navy"  onclick="bulkAction('bulk_status','접수중')">선택 접수중 처리</button>
      <button type="button" class="btn btn-red"   onclick="bulkAction('bulk_delete','')">선택삭제</button>
    </div>
  </div>

  <!-- 요약 -->
  <div class="stat-row">
    <div class="stat"><span>오늘 접수</span><b><?= number_format($stats['today']) ?></b></div>
    <div class="stat"><span>이번주 접수</span><b><?= number_format($stats['week']) ?></b></div>
    <div class="stat"><span>전체 접수</span><b><?= number_format($stats['all']) ?></b></div>
    <div class="stat hl"><span>미처리 (접수중)</span><b><?= number_format($stats['new']) ?></b></div>
  </div>

  <!-- 검색 필터 -->
  <form class="filter" method="get">
    <div class="filter-row">
      <label class="fl">신청날짜</label>
      <input type="date" name="sdate" value="<?= h($f['sdate']) ?>"> ~
      <input type="date" name="edate" value="<?= h($f['edate']) ?>">
      <span class="quick">
        <?php foreach (['today' => '오늘', 'yesterday' => '어제', 'this_week' => '이번주', 'this_month' => '이번달', 'last_week' => '지난주', 'last_month' => '지난달', '' => '전체'] as $qk => $ql): ?>
          <a class="qbtn" href="?quick=<?= h($qk) ?>"><?= h($ql) ?></a>
        <?php endforeach; ?>
      </span>
    </div>
    <div class="filter-row">
      <label class="fl">상태</label>
      <select name="status">
        <option value="">전체 상태</option>
        <?php foreach (STATUS_LIST as $s): ?>
          <option value="<?= h($s) ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= h($s) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="sfield">
        <?php foreach (['name' => '성함', 'phone' => '연락처', 'keyword' => '검색어', 'ip' => '접속IP'] as $fk => $fl): ?>
          <option value="<?= h($fk) ?>" <?= $f['sfield'] === $fk ? 'selected' : '' ?>><?= h($fl) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="sword" value="<?= h($f['sword']) ?>" placeholder="검색어 입력">
      <button type="submit" class="btn btn-pink">검색</button>
      <a class="btn btn-ghost" href="index.php">초기화</a>
      <span class="spacer"></span>
      <a class="btn btn-green" href="export.php?type=xls&<?= h($qsBase) ?>">엑셀(XLS) 다운</a>
      <a class="btn btn-green" href="export.php?type=csv&<?= h($qsBase) ?>">CSV 다운</a>
    </div>
  </form>

  <!-- 목록 -->
  <div class="table-meta">
    총 <b><?= number_format($total) ?></b>건
    <select onchange="location.href='?<?= h(http_build_query(array_diff_key($_GET, ['pp' => 1, 'page' => 1]))) ?>&pp='+this.value">
      <?php foreach ([20, 50, 100] as $pp): ?>
        <option value="<?= $pp ?>" <?= $perPage === $pp ? 'selected' : '' ?>><?= $pp ?>개씩</option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="table-scroll">
  <table class="board">
    <thead>
      <tr>
        <th class="w-chk"><input type="checkbox" id="chkAll"></th>
        <th>번호</th>
        <th>고유키</th>
        <th>신청일</th>
        <th>성함</th>
        <th>연락처</th>
        <th>접속IP</th>
        <th>상품</th>
        <th>금액</th>
        <th>접수상태</th>
        <th>접속경로</th>
        <th>검색어</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="13" class="empty">접수된 문의가 없습니다.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr data-id="<?= (int)$r['id'] ?>">
        <td class="w-chk"><input type="checkbox" class="chk" value="<?= (int)$r['id'] ?>"></td>
        <td><?= (int)$r['id'] ?></td>
        <td class="dim"><?= (int)$r['uid'] ?></td>
        <td class="dim"><?= h($r['created_at']) ?></td>
        <td class="strong"><?= h($r['name']) ?></td>
        <td><?= h($r['phone']) ?></td>
        <td class="dim"><?= h($r['ip']) ?></td>
        <td><?= h($r['product']) ?></td>
        <td><?= h($r['amount']) ?></td>
        <td>
          <select class="st-select <?= status_class($r['status']) ?>" onchange="changeStatus(<?= (int)$r['id'] ?>, this)">
            <?php foreach (STATUS_LIST as $s): ?>
              <option value="<?= h($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= h($s) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><span class="ch-badge"><?= h($r['channel']) ?></span></td>
        <td class="kw"><?= h(keyword_label($r)) ?></td>
        <td class="nowrap">
          <a class="btn btn-sm btn-navy" href="view.php?id=<?= (int)$r['id'] ?>">상세</a>
          <button type="button" class="btn btn-sm btn-red" onclick="delOne(<?= (int)$r['id'] ?>)">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <!-- 페이지네이션 -->
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

<form id="actForm" method="post" action="action.php" style="display:none">
  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
  <input type="hidden" name="act" value="">
  <input type="hidden" name="status" value="">
  <input type="hidden" name="ids" value="">
  <input type="hidden" name="back" value="">
</form>

<script>
const CSRF = <?= json_encode($csrf) ?>;

/* 전체선택 */
document.getElementById('chkAll').addEventListener('change', e => {
  document.querySelectorAll('.chk').forEach(c => c.checked = e.target.checked);
});

function checkedIds() {
  return [...document.querySelectorAll('.chk:checked')].map(c => c.value);
}

/* 인라인 상태 변경 */
async function changeStatus(id, sel) {
  const res = await fetch('action.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({csrf: CSRF, act: 'status', ids: id, status: sel.value, ajax: '1'}),
  });
  const j = await res.json().catch(() => ({ok: false}));
  if (j.ok) {
    sel.className = 'st-select ' + (j.cls || '');
  } else {
    alert(j.msg || '상태 변경에 실패했습니다.');
    location.reload();
  }
}

/* 일괄 처리 */
function bulkAction(act, status) {
  const ids = checkedIds();
  if (!ids.length) { alert('항목을 먼저 선택하세요.'); return; }
  const label = act === 'bulk_delete' ? '삭제' : `'${status}' 처리`;
  if (!confirm(`선택한 ${ids.length}건을 ${label}하시겠습니까?`)) return;
  const fm = document.getElementById('actForm');
  fm.act.value = act === 'bulk_delete' ? 'delete' : 'status';
  fm.status.value = status;
  fm.ids.value = ids.join(',');
  fm.back.value = location.search;
  fm.submit();
}

/* 단건 삭제 */
function delOne(id) {
  if (!confirm('이 접수 건을 삭제하시겠습니까?')) return;
  const fm = document.getElementById('actForm');
  fm.act.value = 'delete';
  fm.ids.value = id;
  fm.back.value = location.search;
  fm.submit();
}
</script>
<?php admin_page_end(); ?>

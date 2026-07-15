<?php
declare(strict_types=1);

/* 접수 건 상세 — 전체 UTM/유입 정보 + 메모 + 상태 변경 */

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/_layout.php';

send_security_headers();
admin_session_boot();
admin_require_login();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM pm_apply WHERE id = ?');
$st->execute([$id]);
$r = $st->fetch();

if (!$r) {
    http_response_code(404);
    exit('존재하지 않는 접수 건입니다.');
}
$csrf = csrf_token();

admin_page_start('접수 상세 #' . (int)$r['id'], 'apply');
?>
<main class="wrap wrap-narrow">
  <div class="page-head">
    <h1>접수 상세 <span class="dim">#<?= (int)$r['id'] ?> (고유키 <?= (int)$r['uid'] ?>)</span></h1>
    <a class="btn btn-ghost" href="index.php">← 목록으로</a>
  </div>

  <?php if (!empty($_GET['saved'])): ?>
    <p class="flash-ok">메모가 저장되었습니다.</p>
  <?php endif; ?>

  <div class="detail-grid">
    <section class="card">
      <h2>신청 정보</h2>
      <table class="kv">
        <tr><th>신청일</th><td><?= h($r['created_at']) ?></td></tr>
        <tr><th>성함</th><td class="strong"><?= h($r['name']) ?></td></tr>
        <tr><th>연락처</th><td><a href="tel:<?= h($r['phone']) ?>"><?= h($r['phone']) ?></a></td></tr>
        <tr><th>상품</th><td><?= h($r['product']) ?></td></tr>
        <tr><th>희망금액</th><td><?= h($r['amount']) ?></td></tr>
        <tr><th>접속 IP</th><td><?= h($r['ip']) ?></td></tr>
        <tr><th>최종수정</th><td><?= h($r['updated_at']) ?></td></tr>
        <tr>
          <th>접수상태</th>
          <td>
            <form method="post" action="action.php" class="inline-form">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="act" value="status">
              <input type="hidden" name="ids" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="back" value="">
              <select name="status" class="st-select <?= status_class($r['status']) ?>">
                <?php foreach (STATUS_LIST as $s): ?>
                  <option value="<?= h($s) ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-sm btn-blue" onclick="this.form.back.value='?id=<?= (int)$r['id'] ?>'; this.form.action='action.php';">변경</button>
            </form>
          </td>
        </tr>
      </table>
    </section>

    <section class="card">
      <h2>유입 경로 (UTM)</h2>
      <table class="kv">
        <tr><th>접속경로</th><td><span class="ch-badge"><?= h($r['channel']) ?></span></td></tr>
        <tr><th>검색어</th><td><?= h($r['keyword']) ?: '<span class="dim">-</span>' ?></td></tr>
        <tr><th>utm_source</th><td><?= h($r['utm_source']) ?: '<span class="dim">-</span>' ?></td></tr>
        <tr><th>utm_medium</th><td><?= h($r['utm_medium']) ?: '<span class="dim">-</span>' ?></td></tr>
        <tr><th>utm_campaign</th><td><?= h($r['utm_campaign']) ?: '<span class="dim">-</span>' ?></td></tr>
        <tr><th>utm_term</th><td><?= h($r['utm_term']) ?: '<span class="dim">-</span>' ?></td></tr>
        <tr><th>utm_content</th><td><?= h($r['utm_content']) ?: '<span class="dim">-</span>' ?></td></tr>
        <tr><th>리퍼러</th><td class="break"><?= h($r['referrer']) ?: '<span class="dim">-</span>' ?></td></tr>
        <tr><th>랜딩 URL</th><td class="break"><?= h($r['landing_url']) ?: '<span class="dim">-</span>' ?></td></tr>
        <tr><th>브라우저</th><td class="break dim"><?= h($r['user_agent']) ?: '-' ?></td></tr>
      </table>
    </section>
  </div>

  <section class="card">
    <h2>상담 메모</h2>
    <form method="post" action="action.php">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="act" value="memo">
      <input type="hidden" name="ids" value="<?= (int)$r['id'] ?>">
      <textarea name="memo" rows="6" class="memo" placeholder="상담 내용, 통화 기록 등을 남기세요."><?= h($r['memo']) ?></textarea>
      <div class="right"><button type="submit" class="btn btn-blue">메모 저장</button></div>
    </form>
  </section>
</main>
<?php admin_page_end(); ?>

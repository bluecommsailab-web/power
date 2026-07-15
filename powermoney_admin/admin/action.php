<?php
declare(strict_types=1);

/* 상태 변경 / 삭제 / 메모 저장 처리 (POST 전용, CSRF 필수) */

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';

send_security_headers();
admin_session_boot();
admin_require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('POST only');
}
csrf_verify_or_die();

$act    = $_POST['act'] ?? '';
$isAjax = ($_POST['ajax'] ?? '') === '1';
$back   = 'index.php' . (preg_match('/^\?[\w=&%\-.]*$/', $_POST['back'] ?? '') ? $_POST['back'] : '');

/* id 목록 파싱 — 숫자만 허용 */
$ids = array_values(array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')), fn($v) => $v > 0));

function finish(bool $ok, string $msg, string $back, bool $isAjax, array $extra = []): never
{
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'msg' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . $back);
    }
    exit;
}

if (!$ids) {
    finish(false, '대상이 없습니다.', $back, $isAjax);
}
$ph = implode(',', array_fill(0, count($ids), '?'));

switch ($act) {
    case 'status':
        $status = $_POST['status'] ?? '';
        if (!in_array($status, STATUS_LIST, true)) {
            finish(false, '잘못된 상태값입니다.', $back, $isAjax);
        }
        $st = db()->prepare("UPDATE pm_apply SET status = ?, updated_at = ? WHERE id IN ($ph)");
        $st->execute(array_merge([$status, date('Y-m-d H:i:s')], $ids));
        finish(true, '변경되었습니다.', $back, $isAjax, ['cls' => status_class($status)]);

    case 'delete':
        $st = db()->prepare("DELETE FROM pm_apply WHERE id IN ($ph)");
        $st->execute($ids);
        finish(true, '삭제되었습니다.', $back, $isAjax);

    case 'memo':
        $memo = mb_substr($_POST['memo'] ?? '', 0, 2000);
        $st = db()->prepare('UPDATE pm_apply SET memo = ?, updated_at = ? WHERE id = ?');
        $st->execute([$memo, date('Y-m-d H:i:s'), $ids[0]]);
        finish(true, '메모가 저장되었습니다.', 'view.php?id=' . $ids[0] . '&saved=1', $isAjax);

    default:
        finish(false, '알 수 없는 요청입니다.', $back, $isAjax);
}

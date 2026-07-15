<?php
declare(strict_types=1);

/* 엑셀(XLS) / CSV 다운로드 — 목록과 동일한 필터 적용 */

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/helpers.php';

send_security_headers();
admin_session_boot();
admin_require_login();

$filter = build_apply_filter($_GET);

$st = db()->prepare("SELECT * FROM pm_apply {$filter['where']} ORDER BY id DESC");
$st->execute($filter['params']);
$rows = $st->fetchAll();

$headers = ['번호', '고유키', '신청일', '성함', '연락처', '접속IP', '상품', '금액', '접수상태',
            '접속경로', '검색어', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            '리퍼러', '랜딩URL', '메모'];

$line = fn(array $r): array => [
    $r['id'], $r['uid'], $r['created_at'], $r['name'], $r['phone'], $r['ip'],
    $r['product'], $r['amount'], $r['status'], $r['channel'], $r['keyword'],
    $r['utm_source'], $r['utm_medium'], $r['utm_campaign'], $r['utm_term'], $r['utm_content'],
    $r['referrer'], $r['landing_url'], $r['memo'],
];

$fname = 'powermoney_' . date('Ymd_His');
$type  = ($_GET['type'] ?? 'csv') === 'xls' ? 'xls' : 'csv';

if ($type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$fname.csv\"");
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM — 엑셀에서 한글 깨짐 방지
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, $line($r));
    }
    fclose($out);
    exit;
}

/* XLS: 엑셀이 열 수 있는 HTML 테이블 방식 */
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$fname.xls\"");
echo "\xEF\xBB\xBF";
echo '<table border="1"><tr>';
foreach ($headers as $hcell) {
    echo '<th>' . h($hcell) . '</th>';
}
echo '</tr>';
foreach ($rows as $r) {
    echo '<tr>';
    foreach ($line($r) as $i => $cell) {
        // 연락처(4)·고유키(1)는 문자열로 강제해 앞자리 0 보존
        $style = in_array($i, [1, 4], true) ? ' style="mso-number-format:\'@\'"' : '';
        echo "<td$style>" . h((string)$cell) . '</td>';
    }
    echo '</tr>';
}
echo '</table>';

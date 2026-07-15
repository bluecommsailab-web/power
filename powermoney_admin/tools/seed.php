<?php
declare(strict_types=1);

/* 로컬 테스트용 더미 데이터 생성 (CLI 전용)
 *   실행: php tools/seed.php [건수]
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI 전용입니다.');
}

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

$count = max(1, (int)($argv[1] ?? 30));
$pdo   = db();

$names    = ['김윤성', '김종록', '김혜지', '김아림', '전정현', '손승모', '김명철', '김주관', '변상기', '김경희', '황승호', '이도현', '박서연', '최민준'];
$sources  = [
    ['naver',  'cpc',     '사업자대출',   'https://search.naver.com/search.naver?query=사업자대출'],
    ['naver',  'cpc',     '소상공인대출', 'https://search.naver.com/search.naver?query=소상공인대출'],
    ['google', 'cpc',     '기업대출',     'https://www.google.com/search?q=기업대출'],
    ['daum',   'organic', '카드매출대출', 'https://search.daum.net/search?q=카드매출대출'],
    ['',       '',        '',            ''],  // 직접유입
    ['kakao',  'message', '',            'https://talk.kakao.com/'],
];
$statuses = STATUS_LIST;

$ins = $pdo->prepare('
    INSERT INTO pm_apply
        (uid, created_at, updated_at, name, phone, product, amount, ip, status,
         channel, keyword, utm_source, utm_medium, utm_campaign, utm_term, utm_content,
         referrer, landing_url, user_agent, memo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

$made = 0;
for ($i = 0; $i < $count; $i++) {
    $src  = $sources[array_rand($sources)];
    $when = date('Y-m-d H:i:s', time() - random_int(0, 12 * 86400));
    $ip   = random_int(58, 223) . '.' . random_int(1, 254) . '.' . random_int(1, 254) . '.' . random_int(1, 254);
    $chan = detect_channel($src[0], $src[3]);
    try {
        $ins->execute([
            random_int(10000, 99999), $when, $when,
            $names[array_rand($names)],
            '010-' . random_int(2000, 9999) . '-' . random_int(1000, 9999),
            PRODUCT_LIST[array_rand(PRODUCT_LIST)],
            AMOUNT_LIST[array_rand(AMOUNT_LIST)],
            $ip,
            $statuses[array_rand($statuses)],
            $chan, $src[2], $src[0], $src[1],
            $src[0] ? 'loan_2026' : '', $src[2], '',
            $src[3], 'https://example.com/?utm_source=' . $src[0],
            'Mozilla/5.0 (seed)', '',
        ]);
        $made++;
    } catch (PDOException $e) {
        // uid 충돌 시 건너뜀
    }
}
echo "더미 데이터 {$made}건 생성 완료\n";

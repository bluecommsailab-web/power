<?php
declare(strict_types=1);

/* 로컬 테스트용 접속 로그 더미 데이터 (CLI 전용)
 *   실행: php tools/seed_visits.php [건수]
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI 전용입니다.');
}

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

$count = max(1, (int)($argv[1] ?? 80));
$pdo   = db();

$uas = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0',
    'Mozilla/5.0 (Linux; Android 14; SM-S928N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 14; SM-S928N) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/25.0 Chrome/121.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Whale/3.25 Chrome/122.0.0.0 Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 KAKAOTALK 10.8.0',
];
$paths = [
    ['naver',  '대부업체대출조회', 'https://search.naver.com/search.naver?query=대부업체대출조회'],
    ['naver',  '추가대출',        'https://search.naver.com/search.naver?query=추가대출'],
    ['naver',  '사업자대출',      'https://search.naver.com/search.naver?query=사업자대출'],
    ['google', '기업대출',        'https://www.google.com/search?q=기업대출'],
    ['daum',   '소상공인대출',    'https://search.daum.net/search?q=소상공인대출'],
    ['',       '',               ''],
    ['',       '',               ''],
    ['kakao',  '',               'https://talk.kakao.com/'],
    ['instagram', '',            'https://l.instagram.com/'],
];

$ins = $pdo->prepare('
    INSERT INTO pm_visit
        (created_at, ip, channel, keyword, utm_source, referrer, landing_url, browser, os, device, user_agent)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

for ($i = 0; $i < $count; $i++) {
    $src  = $paths[array_rand($paths)];
    $ua   = $uas[array_rand($uas)];
    $when = date('Y-m-d H:i:s', time() - random_int(0, 7 * 86400));
    $ip   = random_int(1, 223) . '.' . random_int(1, 254) . '.' . random_int(1, 254) . '.' . random_int(1, 254);
    [$browser, $os, $device] = parse_ua($ua);
    $ins->execute([
        $when, $ip,
        detect_channel($src[0], $src[2]),
        $src[1], $src[0], $src[2],
        'https://example.com/' . ($src[0] ? '?utm_source=' . $src[0] : ''),
        $browser, $os, $device, $ua,
    ]);
}
echo "접속 로그 더미 {$count}건 생성 완료\n";

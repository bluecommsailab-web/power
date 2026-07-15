<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/* =====================================================
 *  공통 헬퍼: 출력 이스케이프 / 유입경로 판별 / 필터 빌더
 * ===================================================== */

/** XSS 방지 출력 이스케이프 */
function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** utm_source·리퍼러로 접속경로(매체) 판별 */
function detect_channel(string $utmSource, string $referrer): string
{
    $map = [
        'naver'     => '네이버',
        'daum'      => '다음',
        'kakao'     => '카카오',
        'google'    => '구글',
        'youtube'   => '유튜브',
        'instagram' => '인스타그램',
        'facebook'  => '페이스북',
        'meta'      => '페이스북',
        'band'      => '밴드',
        'nate'      => '네이트',
        'bing'      => '빙',
        'sms'       => '문자',
        'blog'      => '블로그',
    ];

    $src = strtolower(trim($utmSource));
    if ($src !== '') {
        foreach ($map as $key => $label) {
            if (str_contains($src, $key)) {
                return $label;
            }
        }
        return mb_substr($utmSource, 0, 20); // 알 수 없는 소스는 원문 표기
    }

    $host = strtolower((string)parse_url($referrer, PHP_URL_HOST));
    if ($host !== '') {
        foreach ($map as $key => $label) {
            if (str_contains($host, $key)) {
                return $label;
            }
        }
        return mb_substr($host, 0, 30);
    }
    return '직접유입';
}

/** utm_term 또는 검색엔진 리퍼러에서 검색어 추출 */
function extract_keyword(string $utmTerm, string $referrer): string
{
    if (trim($utmTerm) !== '') {
        return mb_substr(trim($utmTerm), 0, 100);
    }
    $query = (string)parse_url($referrer, PHP_URL_QUERY);
    if ($query !== '') {
        parse_str($query, $qs);
        foreach (['query', 'q', 'keyword', 'wd'] as $k) { // naver=query, google/daum=q
            if (!empty($qs[$k]) && is_string($qs[$k])) {
                return mb_substr(trim($qs[$k]), 0, 100);
            }
        }
    }
    return '';
}

/** 검색어 표기: "n_keyword : 사업자대출" 형태 (그누보드 관례) */
function keyword_label(array $row): string
{
    if ($row['keyword'] === '') {
        return '';
    }
    $prefix = match ($row['channel']) {
        '네이버' => 'n_keyword',
        '다음', '카카오' => 'd_keyword',
        '구글'   => 'g_keyword',
        default  => 'keyword',
    };
    return $prefix . ' : ' . $row['keyword'];
}

/**
 * 외부 입력 위생처리 — 제어문자·태그·따옴표·백틱·역슬래시 제거.
 * 스크립트 삽입 시도를 저장 단계에서 무력화한다.
 */
function clean_param(?string $v, int $len = 200): string
{
    $v = preg_replace('/[\x00-\x1F\x7F<>"\'`\\\\]/u', '', trim((string)$v)) ?? '';
    return mb_substr($v, 0, $len);
}

/** URL 입력 위생처리 — http(s) 형식이 아니면 버린다 (javascript: 등 차단) */
function clean_url(?string $v, int $len = 500): string
{
    $v = clean_param($v, $len);
    if ($v === '' || !preg_match('#^https?://#i', $v)) {
        return '';
    }
    return $v;
}

/** User-Agent → [브라우저, OS, 접속기기] */
function parse_ua(string $ua): array
{
    $browser = '기타';
    foreach ([
        'bot|crawl|spider|Yeti|Daum' => 'Bot',
        'KAKAOTALK'      => '카카오톡',
        'NAVER\('        => '네이버앱',
        'Instagram'      => '인스타그램',
        'SamsungBrowser' => 'Samsung',
        'Whale'          => 'Whale',
        'Edg'            => 'Edge',
        'OPR|Opera'      => 'Opera',
        'Firefox'        => 'Firefox',
        'Chrome'         => 'Chrome',
        'Safari'         => 'Safari',
    ] as $pattern => $label) {
        if (preg_match('/' . $pattern . '/i', $ua)) {
            $browser = $label;
            break;
        }
    }

    $os = '기타';
    foreach ([
        'Windows NT'     => 'Windows',
        'Android'        => 'Android',
        'iPhone|iPad|iPod' => 'iOS',
        'Mac OS X'       => 'macOS',
        'Linux'          => 'Linux',
    ] as $pattern => $label) {
        if (preg_match('/' . $pattern . '/i', $ua)) {
            $os = $label;
            break;
        }
    }

    $device = 'PC';
    if (preg_match('/iPad|Tablet|SM-T/i', $ua)) {
        $device = '태블릿';
    } elseif (preg_match('/Mobi|iPhone|Android.*Mobile/i', $ua)) {
        $device = '모바일';
    }

    return [$browser, $os, $device];
}

/** 날짜 빠른선택 → [시작일, 종료일] (Y-m-d) */
function quick_range(string $key): array
{
    $today = date('Y-m-d');
    return match ($key) {
        'today'      => [$today, $today],
        'yesterday'  => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day'))],
        'this_week'  => [date('Y-m-d', strtotime('monday this week')), $today],
        'this_month' => [date('Y-m-01'), $today],
        'last_week'  => [date('Y-m-d', strtotime('monday last week')), date('Y-m-d', strtotime('sunday last week'))],
        'last_month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        default      => ['', ''],
    };
}

/**
 * 목록/엑셀 공용 필터 빌더.
 * GET 파라미터를 화이트리스트 검증 후 WHERE 절과 바인딩 배열로 변환.
 * @return array{where:string, params:array, f:array}
 */
function build_apply_filter(array $g): array
{
    $f = [
        'sdate'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', $g['sdate'] ?? '')  ? $g['sdate'] : '',
        'edate'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', $g['edate'] ?? '')  ? $g['edate'] : '',
        'status' => in_array($g['status'] ?? '', STATUS_LIST, true)          ? $g['status'] : '',
        'sfield' => in_array($g['sfield'] ?? '', ['name', 'phone', 'keyword', 'ip'], true) ? $g['sfield'] : 'name',
        'sword'  => mb_substr(trim($g['sword'] ?? ''), 0, 50),
    ];

    $where  = [];
    $params = [];
    if ($f['sdate'] !== '') {
        $where[]  = 'created_at >= ?';
        $params[] = $f['sdate'] . ' 00:00:00';
    }
    if ($f['edate'] !== '') {
        $where[]  = 'created_at <= ?';
        $params[] = $f['edate'] . ' 23:59:59';
    }
    if ($f['status'] !== '') {
        $where[]  = 'status = ?';
        $params[] = $f['status'];
    }
    if ($f['sword'] !== '') {
        // 컬럼명은 화이트리스트(sfield)에서만 선택되므로 안전
        $where[]  = $f['sfield'] . ' LIKE ?';
        $params[] = '%' . $f['sword'] . '%';
    }

    return [
        'where'  => $where ? 'WHERE ' . implode(' AND ', $where) : '',
        'params' => $params,
        'f'      => $f,
    ];
}

/** 상태별 뱃지 색상 클래스 */
function status_class(string $status): string
{
    return match ($status) {
        '접수중'   => 'st-new',
        '접수완료' => 'st-done',
        '상담'     => 'st-talk',
        '진행'     => 'st-going',
        '부재'     => 'st-away',
        '오류'     => 'st-err',
        '폐기'     => 'st-trash',
        default    => '',
    };
}

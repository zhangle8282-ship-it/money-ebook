<?php
/**
 * 그린청소 관리자 › 틀 내보내기: 다른 사이트를 만들 때 참고할 ‘틀’(구조 · 화면 · 디자인 · 코드)을 zip 하나로 묶습니다.
 * 실제 데이터(문의 · 고객 · 인력 · 계좌 · 정산 · 글 · 사진)와 비밀 값(비밀번호 해시 · 열쇠 · 토큰 · 확인 코드 · config.local.php)은 넣지 않습니다.
 * 손으로 쓴 설명은 app/blueprint/ 에 있고, 표 · 칸 · 메뉴 · 주소 · 색 값은 지금 코드와 DB에서 그때그때 뽑습니다.
 */

const BLUEPRINT_DIR = APP_DIR . '/blueprint';

// 영역: 키 => 이름(표 설명 columns.json 의 area 와 같은 키)
const BLUEPRINT_AREAS = array(
    'inquiries' => '견적 문의 · 고객',
    'settle' => '정산 · 인력',
    'site' => '홈페이지 내용',
    'blog' => '블로그',
    'stats' => '검색 등록 · 통계',
    'admin' => '관리자 · 권한',
);

// 함께 넣는 코드(이 홈페이지에 쓰는 것만). 상대 경로: app/… 는 APP_DIR, public/… 는 PUBLIC_DIR 기준
const BLUEPRINT_CODE = array(
    'app' => array('bootstrap.php', 'config.php', 'db.php', 'helpers.php', 'auth.php', 'routes.php', 'files.php', 'richtext.php',
        'sanitize.php', 'cleaning.php', 'contracts.php', 'onetime.php', 'workers.php', 'blog.php', 'autoblog.php', 'landing.php',
        'drafts.php', 'search.php', 'visits.php', 'usage.php', 'telegram.php', 'blueprint.php'),
    'app/pages' => array('admin.php', 'cleaning.php', 'contracts.php', 'onetime.php', 'workers.php', 'blog.php', 'autoblog.php', 'landing.php'),
    'app/views/admin' => array('layout.php', 'login.php', 'autoblog.php', 'blog_form.php', 'blog_list.php', 'blueprint.php', 'cleaning_account.php',
        'cleaning_inquiries.php', 'cleaning_photos.php', 'cleaning_reviews.php', 'cleaning_site.php', 'contract_form.php', 'contracts_list.php',
        'contracts_month.php', 'contracts_partners.php', 'contracts_roles.php', 'custom_code.php', 'landing_form.php', 'landing_list.php',
        'notes.php', 'onetime_form.php', 'onetime_jobs.php', 'onetime_list.php', 'partner_form.php', 'search_submit.php', 'stock.php',
        'usage.php', 'visits.php', 'worker_form.php', 'workers.php', '_blog_tabs.php', '_cleaner_assign.php', '_contracts_tabs.php',
        '_onetime_tabs.php', '_partner_fields.php', '_split_fields.php', '_stat_block.php'),
    'app/views/cleaning' => '*',
    'public' => array('index.php', '.htaccess'),
    'public/assets' => array('admin.css', 'admin.js', 'green.css', 'green.js'),
);

// 화면 처리 함수가 하는 일(screens.md 에 씀)
const BLUEPRINT_HANDLERS = array(
    'page_health' => '서버 상태 점검(JSON)',
    'page_cleaning_home' => '첫 화면',
    'action_cleaning_inquiry' => '견적 문의 보내기',
    'page_cleaning_privacy' => '개인정보처리방침',
    'cleaning_robots' => '검색 로봇 규칙',
    'cleaning_sitemap' => '사이트맵',
    'blog_rss' => '블로그 RSS',
    'page_blog_list' => '블로그 목록',
    'page_blog_post' => '블로그 글',
    'indexnow_key_file' => 'IndexNow 열쇠 확인 파일',
    'page_landing' => '검색어 페이지',
    'admin_login' => '관리자 로그인',
    'admin_logout' => '로그아웃',
    'admin_cleaning_home' => '관리자 첫 화면(견적 문의로)',
    'admin_inquiries' => '견적 문의 목록',
    'admin_inquiry_action' => '문의 상태 · 메모 바꾸기',
    'admin_cleaning_site' => '홈페이지 관리(회사 정보 · 문구 · 검색 제목)',
    'admin_cleaning_photos' => '사진 관리(현장 · 전후 사진)',
    'admin_cleaning_reviews' => '후기 관리',
    'admin_blog_list' => '블로그 글 목록',
    'admin_blog_form' => '블로그 글 쓰기 · 고치기',
    'admin_blog_delete' => '블로그 글 지우기',
    'admin_blog_publish' => '예약 글 지금 공개',
    'admin_blog_upload' => '본문 사진 올리기',
    'admin_blog_paste' => '붙여넣은 사진 올리기',
    'admin_autoblog' => '자동 글쓰기 설정 · 비밀 열쇠 · 최근 자동 글',
    'admin_stock' => '사진 창고',
    'admin_notes' => '경험 노트',
    'api_auto_plan' => '자동 글쓰기: 오늘 쓸 글 계획(비밀 열쇠)',
    'api_auto_post' => '자동 글쓰기: 글 받기 · 자동 검사(비밀 열쇠)',
    'admin_contracts_month' => '정기청소 이달 정산',
    'admin_contracts_settle' => '정산 단계 체크 · 완료',
    'admin_contracts_list' => '청소 목록(검색 · 지우기)',
    'admin_contract_roles' => '정기청소 역할 고치기',
    'admin_partners' => '파트너 · 계좌 목록',
    'admin_partner_edit' => '파트너 고치기',
    'admin_partner_delete' => '파트너 지우기',
    'admin_contract_form' => '정기청소 계약 만들기 · 고치기',
    'admin_contract_delete' => '계약 지우기',
    'admin_contract_assign' => '청소 담당 배치',
    'admin_account' => '계정 · 비밀번호 · 관리자 권한',
    'admin_custom_code' => '헤드 코드',
    'admin_search_submit' => '검색 등록 · 로봇 허용/차단 · IndexNow',
    'admin_visits' => '유입 경로',
    'admin_usage' => '용량 · 트래픽',
    'admin_onetime_list' => '일회성 정산 목록',
    'admin_onetime_jobs' => '일회성 청소 목록',
    'admin_onetime_form' => '일회성 일 만들기 · 고치기',
    'admin_onetime_settle' => '일회성 정산 단계 · 완료',
    'admin_onetime_delete' => '일회성 일 지우기',
    'admin_onetime_assign' => '청소 담당 배치',
    'admin_onetime_roles' => '일회성 역할 고치기',
    'admin_workers' => '인력 목록 · 지역으로 찾기',
    'admin_worker_form' => '인력 만들기 · 고치기',
    'admin_worker_delete' => '인력 지우기',
    'admin_worker_assign' => '인력을 청소에 배치',
    'admin_landing_list' => '검색어 페이지 목록',
    'admin_landing_form' => '검색어 페이지 만들기 · 고치기',
    'admin_landing_delete' => '검색어 페이지 지우기',
    'admin_blueprint' => '틀 내보내기',
    'admin_blueprint_download' => '틀 묶음(zip) 내려받기',
);

/** 손으로 쓴 설명 JSON 읽기 */
function blueprint_json($name)
{
    $d = json_decode((string) @file_get_contents(BLUEPRINT_DIR . '/' . $name), true);
    return is_array($d) ? $d : array();
}

/** 지금 DB의 표 칸: [[이름, 형식] …] */
function blueprint_live_columns($table)
{
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return array();
    }
    try {
        if (db_driver() === 'sqlite') {
            return array_map(function ($r) {
                return array($r['name'], $r['type']);
            }, q_all('PRAGMA table_info(' . $table . ')'));
        }
        return array_map(function ($r) {
            return array($r['Field'], $r['Type']);
        }, q_all('SHOW COLUMNS FROM ' . $table));
    } catch (Throwable $e) {
        return array();
    }
}

/** 표를 만드는 SQL(지금 DB 기준) */
function blueprint_create_sql($table)
{
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return '';
    }
    try {
        if (db_driver() === 'sqlite') {
            return (string) q_value("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", array($table));
        }
        $r = q_one('SHOW CREATE TABLE ' . $table);
        return (string) ($r['Create Table'] ?? '');
    } catch (Throwable $e) {
        return '';
    }
}

/** 표 · 칸 설명 + 지금 DB 칸을 합친 구조 */
function blueprint_schema()
{
    $out = array();
    foreach (blueprint_json('columns.json') as $table => $info) {
        $desc = (array) ($info['columns'] ?? array());
        $cols = array();
        foreach (blueprint_live_columns($table) as $c) {
            $cols[] = array('name' => $c[0], 'type' => $c[1], 'desc' => $desc[$c[0]] ?? '(설명 없음)');
            unset($desc[$c[0]]);
        }
        foreach ($desc as $name => $d) {
            $cols[] = array('name' => $name, 'type' => '', 'desc' => $d . ' (지금 DB에는 없음)');
        }
        $out[$table] = array('area' => $info['area'] ?? '', 'desc' => $info['desc'] ?? '', 'columns' => $cols);
    }
    return $out;
}

/** 주소 규칙 → 읽기 쉬운 주소 */
function blueprint_path($re)
{
    $p = preg_replace('/^~\^|\$~$/', '', $re);
    $p = strtr($p, array(
        '(?:/settings)?' => '[/settings]', '(\d+)' => '{번호}', '(?:-([^/]*))?' => '[-{주소}]',
        '([^/.]+)' => '{검색어}', '([a-f0-9]{32})' => '{열쇠}', '\.' => '.',
    ));
    return $p === '' ? '/' : $p;
}

/** 화면 · 주소 목록 */
function blueprint_screens()
{
    $menu = array();
    $work = array('inquiries', 'contracts', 'onetime', 'workers');
    foreach (cleaning_admin_menu() as $key => $m) {
        $menu[] = array('key' => $key, 'path' => $m[0], 'label' => $m[1], 'group' => in_array($key, $work, true) ? '업무' : '홈페이지 관리');
    }
    $routes = array();
    foreach (cleaning_routes() as $r) {
        $path = blueprint_path($r[1]);
        $kind = strpos($path, '/api/') === 0 ? 'API' : (strpos($path, '/admin') === 0 ? '관리자' : '공개');
        $routes[] = array('kind' => $kind, 'method' => $r[0], 'path' => $path, 'handler' => $r[2], 'desc' => BLUEPRINT_HANDLERS[$r[2]] ?? '');
    }
    return array('menu' => $menu, 'routes' => $routes);
}

/** CSS 의 :root 색 · 글꼴 값 */
function blueprint_tokens()
{
    $out = array();
    foreach (array('admin.css', 'green.css') as $f) {
        $css = (string) @file_get_contents(PUBLIC_DIR . '/assets/' . $f);
        preg_match_all('/:root\s*\{([^}]*)\}/', $css, $blocks);
        $vars = array();
        foreach ($blocks[1] as $b) {
            preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $b, $m, PREG_SET_ORDER);
            foreach ($m as $v) {
                if (!isset($vars[$v[1]])) {
                    $vars[$v[1]] = trim($v[2]); // 처음 값(화면 폭에 따라 바뀌는 값은 첫 값만)
                }
            }
        }
        $out[$f] = $vars;
    }
    return $out;
}

/** 넣을 코드 파일: [zip 안 경로 => 실제 경로]. config.local.php · 비밀 값이 든 파일은 빼고 */
function blueprint_code_files()
{
    $files = array();
    foreach (BLUEPRINT_CODE as $dir => $names) {
        $base = strpos($dir, 'public') === 0 ? PUBLIC_DIR . substr($dir, 6) : APP_DIR . substr($dir, 3);
        $list = $names === '*' ? array_map('basename', glob($base . '/*.php') ?: array()) : $names;
        foreach ($list as $name) {
            $path = $base . '/' . $name;
            if ($name === 'config.local.php' || !is_file($path)) {
                continue;
            }
            $text = (string) file_get_contents($path);
            // 안전장치: 텔레그램 토큰 · 개인 키처럼 보이는 값이 있으면 넣지 않음
            if (preg_match('/\b\d{8,10}:[A-Za-z0-9_-]{30,}\b|BEGIN [A-Z ]*PRIVATE KEY/', $text)) {
                continue;
            }
            $files['code/' . $dir . '/' . $name] = $path;
        }
    }
    return $files;
}

/** 묶음 안 글 파일들(이름 => 내용) */
function blueprint_documents()
{
    $docs = array();
    $schemaVer = (string) q_value("SELECT v FROM settings WHERE k = 'schema_version'");
    $docs['README.md'] = strtr((string) @file_get_contents(BLUEPRINT_DIR . '/README.md'), array(
        '{{DATE}}' => date('Y-m-d H:i'), '{{VERSION}}' => trim((string) @file_get_contents(APP_DIR . '/VERSION')), '{{SCHEMA}}' => $schemaVer,
    ));
    $docs['areas.md'] = (string) @file_get_contents(BLUEPRINT_DIR . '/areas.md');

    // 표 · 칸
    $schema = blueprint_schema();
    $docs['schema.json'] = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $md = "# 데이터 구조(표 · 칸)\n\n지금 DB(구조 버전 " . $schemaVer . ")에서 뽑은 표와 칸이에요. 시각은 모두 'YYYY-MM-DD HH:MM:SS' 글자, 금액은 원 단위 정수예요.\n";
    $sql = "-- 표를 만드는 SQL(" . (db_driver() === 'sqlite' ? 'SQLite' : 'MySQL') . " 기준). 실제로는 code/app/db.php 의 migrate() 가 버전 순서대로 만들고 고칩니다.\n\n";
    foreach (BLUEPRINT_AREAS as $area => $label) {
        $md .= "\n## " . $label . "\n";
        foreach ($schema as $table => $t) {
            if ($t['area'] !== $area) {
                continue;
            }
            $md .= "\n### " . $table . "\n\n" . $t['desc'] . "\n\n| 칸 | 형식 | 설명 |\n| --- | --- | --- |\n";
            foreach ($t['columns'] as $c) {
                $md .= '| ' . $c['name'] . ' | ' . $c['type'] . ' | ' . str_replace('|', '/', $c['desc']) . " |\n";
            }
            $create = blueprint_create_sql($table);
            if ($create !== '') {
                $sql .= $create . ";\n\n";
            }
        }
    }
    $docs['schema.md'] = $md;
    $docs['schema.sql'] = $sql;

    // 화면 · 주소
    $screens = blueprint_screens();
    $docs['screens.json'] = json_encode($screens, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $md = "# 화면 구조\n\n## 관리자 메뉴\n\n권한이 work 인 아이디는 ‘업무’ 묶음만 보여요.\n\n| 묶음 | 메뉴 | 주소 |\n| --- | --- | --- |\n";
    foreach ($screens['menu'] as $m) {
        $md .= '| ' . $m['group'] . ' | ' . $m['label'] . ' | ' . $m['path'] . " |\n";
    }
    foreach (array('공개', '관리자', 'API') as $kind) {
        $md .= "\n## " . $kind . " 주소\n\n| 방식 | 주소 | 처리 함수 | 하는 일 |\n| --- | --- | --- | --- |\n";
        foreach ($screens['routes'] as $r) {
            if ($r['kind'] === $kind) {
                $md .= '| ' . $r['method'] . ' | `' . $r['path'] . '` | ' . $r['handler'] . ' | ' . $r['desc'] . " |\n";
            }
        }
    }
    $docs['screens.md'] = $md;

    // 설정 키
    $settings = blueprint_json('settings.json');
    $docs['settings.json'] = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $md = "# 설정 키(settings 표)\n\n" . ($settings['_about'] ?? '') . "\n";
    foreach ((array) ($settings['groups'] ?? array()) as $g) {
        $md .= "\n## " . $g['name'] . "\n\n| 키 | 하는 일 | 가짜 예시 | 비밀 |\n| --- | --- | --- | --- |\n";
        foreach ((array) $g['keys'] as $k => $v) {
            $md .= '| ' . $k . ' | ' . str_replace('|', '/', (string) $v['desc']) . ' | ' . ($v['example'] !== '' ? '`' . str_replace('|', '/', (string) $v['example']) . '`' : '') . ' | ' . (!empty($v['secret']) ? '비밀(새로 만들기)' : '') . " |\n";
        }
    }
    $docs['settings.md'] = $md;

    // 가짜 예시 데이터
    foreach (blueprint_json('samples.json') as $table => $rows) {
        if ($table !== '_about') {
            $docs['samples/' . $table . '.json'] = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }

    // 디자인: 설명 + 색 · 글꼴 값
    $tokens = blueprint_tokens();
    $docs['design/tokens.json'] = json_encode($tokens, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $md = (string) @file_get_contents(BLUEPRINT_DIR . '/design.md') . "\n## 색 · 글꼴 값\n";
    foreach ($tokens as $file => $vars) {
        $md .= "\n### " . $file . "\n\n| 변수 | 값 |\n| --- | --- |\n";
        foreach ($vars as $k => $v) {
            $md .= '| `' . $k . '` | `' . str_replace('|', '/', $v) . "` |\n";
        }
    }
    $docs['design/design.md'] = $md;
    return $docs;
}

/** zip 만들기. 반환: 임시 파일 경로(다 쓰면 지울 것) */
function blueprint_build_zip()
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('서버에 zip 기능이 없어요.');
    }
    $tmp = tempnam(sys_get_temp_dir(), 'bp');
    $zip = new ZipArchive();
    if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('zip 파일을 만들 수 없어요.');
    }
    $root = 'site-blueprint-' . date('Ymd') . '/';
    foreach (blueprint_documents() as $name => $text) {
        $zip->addFromString($root . $name, $text);
    }
    foreach (array('admin.css', 'green.css', 'admin.js', 'green.js') as $f) {
        if (is_file(PUBLIC_DIR . '/assets/' . $f)) {
            $zip->addFile(PUBLIC_DIR . '/assets/' . $f, $root . 'design/' . $f);
        }
    }
    foreach (blueprint_code_files() as $name => $path) {
        $zip->addFile($path, $root . $name);
    }
    $zip->close();
    return $tmp;
}

/** 관리 화면에 보여 줄 숫자(표 · 칸 · 주소 · 코드 파일 수) */
function blueprint_summary()
{
    $schema = blueprint_schema();
    $cols = 0;
    foreach ($schema as $t) {
        $cols += count($t['columns']);
    }
    $screens = blueprint_screens();
    $keys = 0;
    foreach ((array) (blueprint_json('settings.json')['groups'] ?? array()) as $g) {
        $keys += count((array) $g['keys']);
    }
    return array('tables' => count($schema), 'columns' => $cols, 'routes' => count($screens['routes']), 'menu' => count($screens['menu']),
        'settings' => $keys, 'samples' => count(blueprint_json('samples.json')) - 1, 'code' => count(blueprint_code_files()));
}

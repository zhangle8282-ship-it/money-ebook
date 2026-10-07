<?php
/**
 * DB 연결과 테이블 생성. SQLite(기본)와 MySQL 둘 다 동작하는 SQL만 씁니다.
 */

function db()
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $cfg = config('db');
    $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
    if (db_driver() === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    } else {
        $pdo->exec("SET NAMES utf8mb4");
    }
    migrate($pdo);
    return $pdo;
}

function db_driver()
{
    return strtolower(strtok(config('db')['dsn'], ':'));
}

function q($sql, $params = array())
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function q_all($sql, $params = array())
{
    return q($sql, $params)->fetchAll();
}

function q_one($sql, $params = array())
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function q_value($sql, $params = array())
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function q_insert($table, $row)
{
    $cols = array_keys($row);
    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
    q($sql, array_values($row));
    return (int) db()->lastInsertId();
}

function q_update($table, $id, $row)
{
    $sets = array();
    foreach (array_keys($row) as $col) {
        $sets[] = $col . ' = ?';
    }
    $params = array_values($row);
    $params[] = $id;
    q('UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
}

/** 테이블이 없으면 만듭니다. 스키마를 바꿀 때는 버전을 올리고 아래에 단계를 추가하세요. */
function migrate(PDO $pdo)
{
    $sqlite = db_driver() === 'sqlite';
    $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    $long = $sqlite ? 'TEXT' : 'MEDIUMTEXT';
    $tail = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (k VARCHAR(64) NOT NULL PRIMARY KEY, v ' . $long . ')' . $tail);
    $version = (int) $pdo->query("SELECT v FROM settings WHERE k = 'schema_version'")->fetchColumn();
    if ($version < 1) {
        migrate_v1($pdo, $id, $long, $tail, $sqlite);
    }
    if ($version < 2) {
        // 2: 뷰어에서 읽던 위치(기기가 달라도 이어 읽기)
        $pdo->exec('CREATE TABLE IF NOT EXISTS reading_progress (
            user_id INT NOT NULL, book_id INT NOT NULL, position VARCHAR(255) NOT NULL DEFAULT \'\',
            percent INT NOT NULL DEFAULT 0, updated_at VARCHAR(19) NOT NULL, PRIMARY KEY (user_id, book_id))' . $tail);
        $pdo->prepare("UPDATE settings SET v = '2' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 3) {
        // 3: 나의 마켓(마켓 운영 신청) · 추천인 · 출금
        $pdo->exec("CREATE TABLE IF NOT EXISTS market_applications (
            id $id, app_no VARCHAR(32) NOT NULL UNIQUE, user_id INT NOT NULL,
            months INT NOT NULL, monthly_price INT NOT NULL, discount INT NOT NULL DEFAULT 0, total INT NOT NULL,
            domain VARCHAR(190) NOT NULL DEFAULT '', market_name VARCHAR(100) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT '',
            depositor VARCHAR(60) NOT NULL, referrer_user_id INT NULL, referral_code VARCHAR(20) NOT NULL DEFAULT '',
            commission INT NOT NULL DEFAULT 0, status VARCHAR(12) NOT NULL DEFAULT 'pending',
            bank_name VARCHAR(60) NOT NULL DEFAULT '', bank_account VARCHAR(60) NOT NULL DEFAULT '', bank_holder VARCHAR(60) NOT NULL DEFAULT '',
            admin_note TEXT, starts_at VARCHAR(10) NULL, ends_at VARCHAR(10) NULL,
            created_at VARCHAR(19) NOT NULL, paid_at VARCHAR(19) NULL, cancelled_at VARCHAR(19) NULL)" . $tail);
        $pdo->exec("CREATE TABLE IF NOT EXISTS referrers (
            user_id INT NOT NULL PRIMARY KEY, code VARCHAR(20) NULL UNIQUE, status VARCHAR(12) NOT NULL DEFAULT 'pending',
            intro TEXT, admin_memo TEXT, bank_name VARCHAR(60) NOT NULL DEFAULT '', bank_account VARCHAR(60) NOT NULL DEFAULT '',
            bank_holder VARCHAR(60) NOT NULL DEFAULT '', created_at VARCHAR(19) NOT NULL, decided_at VARCHAR(19) NULL)" . $tail);
        $pdo->exec("CREATE TABLE IF NOT EXISTS withdrawals (
            id $id, user_id INT NOT NULL, amount INT NOT NULL, bank_name VARCHAR(60) NOT NULL, bank_account VARCHAR(60) NOT NULL,
            bank_holder VARCHAR(60) NOT NULL, status VARCHAR(12) NOT NULL DEFAULT 'requested', admin_memo TEXT,
            created_at VARCHAR(19) NOT NULL, processed_at VARCHAR(19) NULL)" . $tail);
        $pdo->exec('CREATE INDEX idx_market_user ON market_applications (user_id)');
        $pdo->exec('CREATE INDEX idx_market_referrer ON market_applications (referrer_user_id, status)');
        $pdo->exec('CREATE INDEX idx_withdrawals_user ON withdrawals (user_id, status)');
        $pdo->prepare("UPDATE settings SET v = '3' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 4) {
        // 4: 나의 마켓을 기간제에서 솔루션 판매로(상품 종류, 설치용 호스팅 정보), 오픈마켓(판매자 입점)
        $pdo->exec("ALTER TABLE market_applications ADD COLUMN product VARCHAR(20) NOT NULL DEFAULT ''");
        // 설치용 서버호스팅 정보(비밀번호는 암호화해서 저장)
        $pdo->exec("ALTER TABLE market_applications ADD COLUMN hosting_url VARCHAR(255) NOT NULL DEFAULT ''");
        $pdo->exec("ALTER TABLE market_applications ADD COLUMN hosting_id VARCHAR(100) NOT NULL DEFAULT ''");
        $pdo->exec('ALTER TABLE market_applications ADD COLUMN hosting_pw TEXT NULL');
        // 오픈마켓: 회원이 올린 전자책(판매자), 판매 시점의 수수료·판매자 몫, 판매 정산 출금
        $pdo->exec('ALTER TABLE books ADD COLUMN seller_user_id INT NULL');
        $pdo->exec('ALTER TABLE books ADD COLUMN review_memo TEXT NULL');
        $pdo->exec('ALTER TABLE order_items ADD COLUMN seller_user_id INT NULL');
        $pdo->exec('ALTER TABLE order_items ADD COLUMN commission_rate INT NOT NULL DEFAULT 0');
        $pdo->exec('ALTER TABLE order_items ADD COLUMN seller_amount INT NOT NULL DEFAULT 0');
        $pdo->exec("ALTER TABLE withdrawals ADD COLUMN kind VARCHAR(12) NOT NULL DEFAULT 'referral'");
        $pdo->exec("CREATE TABLE IF NOT EXISTS sellers (
            user_id INT NOT NULL PRIMARY KEY, bank_name VARCHAR(60) NOT NULL DEFAULT '', bank_account VARCHAR(60) NOT NULL DEFAULT '',
            bank_holder VARCHAR(60) NOT NULL DEFAULT '', created_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->exec('CREATE INDEX idx_books_seller ON books (seller_user_id)');
        $pdo->exec('CREATE INDEX idx_items_seller ON order_items (seller_user_id)');
        $pdo->prepare("UPDATE settings SET v = '4' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 5) {
        // 5: 관리자가 직접 올린 글씨체
        $pdo->exec("CREATE TABLE IF NOT EXISTS fonts (
            id $id, name VARCHAR(60) NOT NULL, kind VARCHAR(12) NOT NULL DEFAULT 'sans-serif',
            file_regular VARCHAR(255) NOT NULL, file_bold VARCHAR(255) NOT NULL DEFAULT '', size INT NOT NULL DEFAULT 0,
            created_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->prepare("UPDATE settings SET v = '5' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 6) {
        // 6: 그린청소 홈페이지 견적 문의
        $pdo->exec("CREATE TABLE IF NOT EXISTS inquiries (
            id $id, kind VARCHAR(20) NOT NULL DEFAULT 'office', name VARCHAR(100) NOT NULL, phone VARCHAR(40) NOT NULL,
            address VARCHAR(255) NOT NULL DEFAULT '', status VARCHAR(12) NOT NULL DEFAULT 'new', memo TEXT,
            ip_hash VARCHAR(64) NOT NULL DEFAULT '', created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->exec('CREATE INDEX idx_inquiries_status ON inquiries (status, id)');
        $pdo->prepare("UPDATE settings SET v = '6' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 7) {
        // 7: 견적 문의 알림 메일을 보냈는지(sent/failed/off)
        $pdo->exec("ALTER TABLE inquiries ADD COLUMN mailed VARCHAR(12) NOT NULL DEFAULT ''");
        $pdo->prepare("UPDATE settings SET v = '7' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 8) {
        // 8: 청소 정기청소 정산(청소별 계약 조건, 달마다 정산)
        $pdo->exec("CREATE TABLE IF NOT EXISTS contracts (
            id $id, name VARCHAR(100) NOT NULL, client VARCHAR(100) NOT NULL DEFAULT '',
            monthly_fee INT NOT NULL DEFAULT 0, invoice INT NOT NULL DEFAULT 1, contract_rate INT NOT NULL DEFAULT 10,
            gap_rate INT NOT NULL DEFAULT 60, gap_name VARCHAR(60) NOT NULL DEFAULT '', eul_name VARCHAR(60) NOT NULL DEFAULT '',
            start_month VARCHAR(7) NOT NULL, end_month VARCHAR(7) NULL, memo TEXT,
            created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->exec("CREATE TABLE IF NOT EXISTS contract_settlements (
            id $id, contract_id INT NOT NULL, month VARCHAR(7) NOT NULL,
            fee INT NOT NULL DEFAULT 0, invoice INT NOT NULL DEFAULT 1, tax INT NOT NULL DEFAULT 0,
            contract_rate INT NOT NULL DEFAULT 10, contract_amount INT NOT NULL DEFAULT 0,
            gap_rate INT NOT NULL DEFAULT 60, gap_amount INT NOT NULL DEFAULT 0, eul_amount INT NOT NULL DEFAULT 0,
            status VARCHAR(12) NOT NULL DEFAULT 'pending', memo TEXT, settled_at VARCHAR(19) NULL,
            created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->exec('CREATE UNIQUE INDEX idx_settlement_month ON contract_settlements (contract_id, month)');
        $pdo->exec('CREATE INDEX idx_settlement_by_month ON contract_settlements (month)');
        $pdo->prepare("UPDATE settings SET v = '8' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 9) {
        // 9: 그린청소 블로그
        $pdo->exec("CREATE TABLE IF NOT EXISTS blog_posts (
            id $id, title VARCHAR(200) NOT NULL, slug VARCHAR(120) NOT NULL DEFAULT '', summary VARCHAR(300) NOT NULL DEFAULT '',
            body $long, cover VARCHAR(255) NOT NULL DEFAULT '', seo_title VARCHAR(200) NOT NULL DEFAULT '', keywords VARCHAR(300) NOT NULL DEFAULT '',
            status VARCHAR(12) NOT NULL DEFAULT 'draft', published_at VARCHAR(19) NULL, views INT NOT NULL DEFAULT 0,
            created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->exec('CREATE INDEX idx_blog_public ON blog_posts (status, published_at)');
        $pdo->prepare("UPDATE settings SET v = '9' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 10) {
        // 10: 정기청소 정산을 갑·을·병으로(병 = 청소담당자, 원천징수 3.3%)
        $pdo->exec("ALTER TABLE contracts ADD COLUMN byeong_name VARCHAR(60) NOT NULL DEFAULT ''");
        $pdo->exec('ALTER TABLE contracts ADD COLUMN withholding INT NOT NULL DEFAULT 1');
        $pdo->exec('ALTER TABLE contract_settlements ADD COLUMN withholding INT NOT NULL DEFAULT 1');
        $pdo->exec('ALTER TABLE contract_settlements ADD COLUMN byeong_amount INT NOT NULL DEFAULT 0');
        $pdo->exec('ALTER TABLE contract_settlements ADD COLUMN withholding_amount INT NOT NULL DEFAULT 0');
        $pdo->exec('ALTER TABLE contract_settlements ADD COLUMN byeong_pay INT NOT NULL DEFAULT 0');
        // 대표파트너의 정산 단계(입금 확인 · 세금계산서 · 청소 담당 지급 · 운영 지급)와 정산한 사람
        foreach (array('step_received', 'step_invoiced', 'step_paid_byeong', 'step_paid_eul') as $col) {
            $pdo->exec("ALTER TABLE contract_settlements ADD COLUMN $col VARCHAR(19) NULL");
        }
        $pdo->exec("ALTER TABLE contract_settlements ADD COLUMN settled_by VARCHAR(60) NOT NULL DEFAULT ''");
        $pdo->prepare("UPDATE settings SET v = '10' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 11) {
        // 11: 파트너(대표·운영·청소 담당)와 지급 계좌, 청소마다 담당 파트너
        $pdo->exec("CREATE TABLE IF NOT EXISTS partners (
            id $id, role VARCHAR(12) NOT NULL, name VARCHAR(60) NOT NULL, phone VARCHAR(40) NOT NULL DEFAULT '',
            bank_name VARCHAR(40) NOT NULL DEFAULT '', bank_account VARCHAR(40) NOT NULL DEFAULT '', bank_holder VARCHAR(60) NOT NULL DEFAULT '',
            memo TEXT, created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)" . $tail);
        foreach (array('gap_partner_id', 'eul_partner_id', 'byeong_partner_id') as $col) {
            $pdo->exec("ALTER TABLE contracts ADD COLUMN $col INT NULL");
        }
        $pdo->prepare("UPDATE settings SET v = '11' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 12) {
        // 12: 첫 화면 검색 제목 · 설명을 주인이 정한 그대로(제목 ‘충북음성청소업체’, 설명은 검색어 6개만).
        //     관리자에서 직접 바꾼 값은 그대로 둡니다.
        $fix = $pdo->prepare('UPDATE settings SET v = ? WHERE k = ? AND v = ?');
        $fix->execute(array('충북음성청소업체', 'gc_seo_title', '충북음성청소업체 | 그린청소'));
        $fix->execute(array('충북음성청소업체, 금왕사무실정기청소, 음성공장청소, 충북혁신도시화장실청소, 진천상가청소, 대소공단청소', 'gc_seo_desc', '충북음성청소업체 그린청소 – 금왕사무실정기청소, 음성공장청소, 충북혁신도시화장실청소, 진천상가청소, 대소공단청소까지. 요일·시간만 정하면 전담 인력이 매번 같은 기준으로 관리합니다. 현장 방문 견적 무료.'));
        $pdo->prepare("UPDATE settings SET v = '12' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 13) {
        // 13: 그린청소 검색어 페이지(지역 · 업종별 소개 페이지, 주소 /음성공장청소 처럼)
        $pdo->exec("CREATE TABLE IF NOT EXISTS landing_pages (
            id $id, slug VARCHAR(120) NOT NULL, title VARCHAR(200) NOT NULL, summary VARCHAR(300) NOT NULL DEFAULT '',
            body $long, cover VARCHAR(255) NOT NULL DEFAULT '', seo_title VARCHAR(200) NOT NULL DEFAULT '', keywords VARCHAR(300) NOT NULL DEFAULT '',
            kind VARCHAR(12) NOT NULL DEFAULT 'office', sort INT NOT NULL DEFAULT 0, status VARCHAR(12) NOT NULL DEFAULT 'published',
            created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->exec('CREATE UNIQUE INDEX ' . ($sqlite ? 'IF NOT EXISTS ' : '') . 'idx_landing_slug ON landing_pages (slug)');
        $pdo->prepare("UPDATE settings SET v = '13' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 14) {
        // 14: 블로그 본문 형식(md: 예전 간단 표시, html: 서식 있는 에디터)
        $cols = $sqlite ? array_column($pdo->query('PRAGMA table_info(blog_posts)')->fetchAll(PDO::FETCH_ASSOC), 'name')
            : $pdo->query('SHOW COLUMNS FROM blog_posts')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('format', $cols, true)) {
            $pdo->exec("ALTER TABLE blog_posts ADD COLUMN format VARCHAR(8) NOT NULL DEFAULT 'md'");
        }
        $pdo->prepare("UPDATE settings SET v = '14' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 15) {
        // 15: 이미 처리한 견적 문의(메모를 적었거나 한 번이라도 저장한 것)는 ‘새 문의’에서 ‘연락함’으로
        $pdo->exec("UPDATE inquiries SET status = 'contacted' WHERE status = 'new' AND (memo <> '' OR updated_at > created_at)");
        $pdo->prepare("UPDATE settings SET v = '15' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 16) {
        // 16: 인력 배치 정보(이름 · 연락처 · 커버 가능 지역 · 원하는 방식)
        $pdo->exec("CREATE TABLE IF NOT EXISTS workers (
            id $id, name VARCHAR(60) NOT NULL, phone VARCHAR(40) NOT NULL DEFAULT '', regions VARCHAR(600) NOT NULL DEFAULT '',
            method VARCHAR(12) NOT NULL DEFAULT 'commission', memo TEXT, created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->prepare("UPDATE settings SET v = '16' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 17) {
        // 17: 인력 배치에서 고른 청소 담당 파트너가 어느 사람인지(partners.worker_id)
        $cols = $sqlite ? array_column($pdo->query('PRAGMA table_info(partners)')->fetchAll(PDO::FETCH_ASSOC), 'name')
            : $pdo->query('SHOW COLUMNS FROM partners')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('worker_id', $cols, true)) {
            $pdo->exec('ALTER TABLE partners ADD COLUMN worker_id INT NULL');
        }
        $pdo->prepare("UPDATE settings SET v = '17' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 18) {
        // 18: 인력 배치 구성(남자 · 여자 · 부부 · 남매 · 모녀 · 모자 · 친구 · 기타)과 기타 관계 글
        $cols = $sqlite ? array_column($pdo->query('PRAGMA table_info(workers)')->fetchAll(PDO::FETCH_ASSOC), 'name')
            : $pdo->query('SHOW COLUMNS FROM workers')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('team', $cols, true)) {
            $pdo->exec("ALTER TABLE workers ADD COLUMN team VARCHAR(20) NOT NULL DEFAULT ''");
        }
        if (!in_array('team_note', $cols, true)) {
            $pdo->exec("ALTER TABLE workers ADD COLUMN team_note VARCHAR(60) NOT NULL DEFAULT ''");
        }
        $pdo->prepare("UPDATE settings SET v = '18' WHERE k = 'schema_version'")->execute();
    }
    if ($version < 19) {
        // 19: 일회성 정산(입주청소처럼 한 번 하는 일): 조건 · 나눈 금액(저장할 때 계산해 둠) · 정산 단계
        $pdo->exec("CREATE TABLE IF NOT EXISTS onetime_jobs (
            id $id, name VARCHAR(100) NOT NULL, client VARCHAR(100) NOT NULL DEFAULT '', work_date VARCHAR(10) NOT NULL,
            fee INT NOT NULL DEFAULT 0, invoice INT NOT NULL DEFAULT 1, contract_rate INT NOT NULL DEFAULT 20, gap_rate INT NOT NULL DEFAULT 60, withholding INT NOT NULL DEFAULT 1,
            gap_partner_id INT NULL, eul_partner_id INT NULL, byeong_partner_id INT NULL,
            gap_name VARCHAR(60) NOT NULL DEFAULT '', eul_name VARCHAR(60) NOT NULL DEFAULT '', byeong_name VARCHAR(60) NOT NULL DEFAULT '',
            tax INT NOT NULL DEFAULT 0, contract_amount INT NOT NULL DEFAULT 0, byeong_amount INT NOT NULL DEFAULT 0, withholding_amount INT NOT NULL DEFAULT 0,
            byeong_pay INT NOT NULL DEFAULT 0, gap_amount INT NOT NULL DEFAULT 0, eul_amount INT NOT NULL DEFAULT 0,
            status VARCHAR(12) NOT NULL DEFAULT 'pending', step_received VARCHAR(19) NULL, step_invoiced VARCHAR(19) NULL,
            step_paid_byeong VARCHAR(19) NULL, step_paid_eul VARCHAR(19) NULL, settled_at VARCHAR(19) NULL, settled_by VARCHAR(64) NOT NULL DEFAULT '',
            memo TEXT, created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)" . $tail);
        $pdo->exec('CREATE INDEX ' . ($sqlite ? 'IF NOT EXISTS ' : '') . 'idx_onetime_date ON onetime_jobs (work_date)');
        $pdo->prepare("UPDATE settings SET v = '19' WHERE k = 'schema_version'")->execute();
    }
}

/** 1: 처음 만드는 표들 */
function migrate_v1(PDO $pdo, $id, $long, $tail, $sqlite)
{
    $tables = array(
        "CREATE TABLE admins (
            id $id, username VARCHAR(64) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at VARCHAR(19) NOT NULL)",
        "CREATE TABLE users (
            id $id, email VARCHAR(190) NOT NULL UNIQUE, name VARCHAR(60) NOT NULL,
            password_hash VARCHAR(255) NOT NULL, created_at VARCHAR(19) NOT NULL)",
        "CREATE TABLE books (
            id $id, title VARCHAR(200) NOT NULL, author VARCHAR(120) NOT NULL DEFAULT '',
            category VARCHAR(60) NOT NULL DEFAULT '', price INT NOT NULL DEFAULT 0, pages INT NULL,
            description TEXT, cover_path VARCHAR(255) NOT NULL DEFAULT '',
            file_path VARCHAR(255) NOT NULL DEFAULT '', file_name VARCHAR(255) NOT NULL DEFAULT '',
            file_size INT NOT NULL DEFAULT 0, file_format VARCHAR(10) NOT NULL DEFAULT '',
            preview_mode VARCHAR(10) NOT NULL DEFAULT 'auto', preview_pages INT NOT NULL DEFAULT 10,
            preview_text $long, preview_html $long, preview_images TEXT,
            status VARCHAR(10) NOT NULL DEFAULT 'draft', published_at VARCHAR(19) NULL,
            created_at VARCHAR(19) NOT NULL, updated_at VARCHAR(19) NOT NULL)",
        "CREATE TABLE orders (
            id $id, order_no VARCHAR(32) NOT NULL UNIQUE, user_id INT NOT NULL, depositor VARCHAR(60) NOT NULL,
            total INT NOT NULL, status VARCHAR(12) NOT NULL DEFAULT 'pending',
            bank_name VARCHAR(60) NOT NULL DEFAULT '', bank_account VARCHAR(60) NOT NULL DEFAULT '',
            bank_holder VARCHAR(60) NOT NULL DEFAULT '', due_at VARCHAR(19) NULL,
            created_at VARCHAR(19) NOT NULL, paid_at VARCHAR(19) NULL, cancelled_at VARCHAR(19) NULL)",
        "CREATE TABLE order_items (
            id $id, order_id INT NOT NULL, book_id INT NOT NULL, title VARCHAR(200) NOT NULL, price INT NOT NULL)",
        "CREATE TABLE reviews (
            id $id, book_id INT NOT NULL, user_id INT NOT NULL, rating INT NOT NULL, body TEXT NOT NULL,
            helpful INT NOT NULL DEFAULT 0, status VARCHAR(10) NOT NULL DEFAULT 'visible', created_at VARCHAR(19) NOT NULL)",
        "CREATE TABLE review_votes (
            review_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (review_id, user_id))",
        "CREATE TABLE login_attempts (
            k VARCHAR(64) NOT NULL PRIMARY KEY, fails INT NOT NULL DEFAULT 0, first_at INT NOT NULL, locked_until INT NOT NULL DEFAULT 0)",
    );
    $indexes = array(
        'CREATE INDEX idx_books_status ON books (status, published_at)',
        'CREATE INDEX idx_orders_user ON orders (user_id, status)',
        'CREATE INDEX idx_items_order ON order_items (order_id)',
        'CREATE INDEX idx_items_book ON order_items (book_id)',
        'CREATE INDEX idx_reviews_book ON reviews (book_id, status)',
    );
    // MySQL은 CREATE TABLE 이 트랜잭션을 자동 커밋하므로 SQLite에서만 묶습니다.
    if ($sqlite) {
        $pdo->beginTransaction();
    }
    try {
        foreach ($tables as $sql) {
            $pdo->exec($sql . $tail);
        }
        foreach ($indexes as $sql) {
            $pdo->exec($sql);
        }
        $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?)')->execute(array('schema_version', '1'));
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

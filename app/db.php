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

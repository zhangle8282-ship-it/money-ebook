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
    if ($version >= 1) {
        return;
    }

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

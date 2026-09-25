<?php
/**
 * 스토어 데이터: 설정, 전자책 조회, 장바구니, 주문(무통장 입금), 리뷰.
 */

/* ───────── 설정 ───────── */

function default_settings()
{
    return array(
        'store_name' => '전자책 스토어',
        'hero_title' => '오늘 읽을 한 권을 찾아보세요',
        'hero_text' => '구매 전에 본문 일부를 무료로 미리 읽어볼 수 있어요.',
        'categories' => '에세이, 소설, 자기계발, 경제·경영, IT',
        'bank_name' => '',
        'bank_account' => '',
        'bank_holder' => '',
        'deposit_days' => '3',
        // 구매자가 파일을 내려받게 할지(0이면 사이트 뷰어로만 읽기)
        'allow_download' => '0',
        // 나의 마켓의 '카페24에서 서버호스팅·도메인 준비하기' 버튼 주소(제휴 링크 등)
        'cafe24_url' => 'https://hosting.cafe24.com/',
        // 카페24 제휴코드(고객이 카페24에서 가입·신청할 때 넣도록 안내)
        'cafe24_code' => '',
        // 오픈마켓: 회원이 자기 전자책을 올려 판매(관리자 승인), 판매 수수료(%)
        'seller_enabled' => '0',
        'seller_commission' => '20',
        'biz_name' => '',
        'biz_owner' => '',
        'biz_number' => '',
        'biz_mail_order' => '',
        'biz_address' => '',
        'biz_phone' => '',
        'biz_email' => '',
        'terms_text' => "제1조 (목적)\n이 약관은 스토어가 제공하는 전자책 판매 서비스의 이용 조건과 절차를 정합니다.\n\n제2조 (결제)\n결제는 무통장 입금으로 진행되며, 입금이 확인된 뒤 내 서재에서 전자책을 바로 읽을 수 있습니다. 입금 기한이 지나면 주문이 취소될 수 있습니다.\n\n제3조 (청약철회)\n전자책은 디지털 콘텐츠 특성상 열람(읽기)이나 다운로드를 시작한 뒤에는 청약철회가 제한됩니다. 열람 전이라면 결제일로부터 7일 이내에 환불을 요청할 수 있습니다.\n\n[사업자 정보와 약관 내용을 실제 운영에 맞게 수정하세요]",
        'privacy_text' => "1. 수집하는 개인정보\n회원가입과 주문 처리를 위해 이메일, 이름, 입금자명을 수집합니다.\n\n2. 이용 목적\n회원 식별, 주문·입금 확인, 전자책 제공, 문의 응대에 사용합니다.\n\n3. 보관 기간\n회원 탈퇴 시 지체 없이 파기합니다. 다만 전자상거래법에 따라 계약·결제 기록은 5년간 보관합니다.\n\n4. 문의\n개인정보 관련 문의는 고객센터 이메일로 보내 주세요.\n\n[실제 운영 내용에 맞게 수정하세요]",
    );
}

function settings($reload = false)
{
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = default_settings();
        foreach (q_all('SELECT k, v FROM settings') as $row) {
            $cache[$row['k']] = $row['v'];
        }
    }
    return $cache;
}

function setting($key)
{
    $s = settings();
    return isset($s[$key]) ? (string) $s[$key] : '';
}

function save_settings($values)
{
    db()->beginTransaction();
    foreach ($values as $k => $v) {
        q('DELETE FROM settings WHERE k = ?', array($k));
        q('INSERT INTO settings (k, v) VALUES (?, ?)', array($k, (string) $v));
    }
    db()->commit();
    settings(true);
}

function categories()
{
    $list = array_map('trim', preg_split('/[,\n]+/u', setting('categories')));
    return array_values(array_unique(array_filter($list, 'strlen')));
}

function bank_ready()
{
    return setting('bank_name') !== '' && setting('bank_account') !== '' && setting('bank_holder') !== '';
}

/** 푸터에 보일 사업자 정보 한 줄(전자상거래법 표시 사항). */
function business_lines()
{
    $parts = array();
    $labels = array(
        'biz_name' => '상호', 'biz_owner' => '대표', 'biz_number' => '사업자등록번호',
        'biz_mail_order' => '통신판매업 신고', 'biz_address' => '주소', 'biz_phone' => '고객센터', 'biz_email' => '이메일',
    );
    foreach ($labels as $key => $label) {
        if (setting($key) !== '') {
            $parts[] = $label . ' ' . setting($key);
        }
    }
    return $parts;
}

/* ───────── 전자책 ───────── */

const BOOK_STATUS = array('draft' => '임시저장', 'review' => '승인 대기', 'rejected' => '반려', 'hidden' => '비공개', 'on_sale' => '판매 중');

// 표지 이미지가 없을 때 쓰는 색 조합(배경, 글자)
const COVER_PALETTES = array(
    array('#2E5E4E', '#F6F4EF'), array('#E9D8A6', '#1D1C1A'), array('#1D1C1A', '#E9D8A6'), array('#D8DDE3', '#1D1C1A'),
    array('#8C4A3C', '#F6F4EF'), array('#B9C7B4', '#1D1C1A'), array('#3F4A5A', '#F6F4EF'), array('#C9B79C', '#1D1C1A'),
);

function cover_colors($book)
{
    return COVER_PALETTES[((int) $book['id'] - 1 + count(COVER_PALETTES)) % count(COVER_PALETTES)];
}

/** 평점·리뷰 수·판매량을 붙인 전자책 조회 SQL. */
function book_select_sql()
{
    return "SELECT b.*, COALESCE(r.avg_rating, 0) AS avg_rating, COALESCE(r.review_count, 0) AS review_count,
            COALESCE(s.sold, 0) AS sold
        FROM books b
        LEFT JOIN (SELECT book_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                   FROM reviews WHERE status = 'visible' GROUP BY book_id) r ON r.book_id = b.id
        LEFT JOIN (SELECT oi.book_id, COUNT(*) AS sold FROM order_items oi
                   JOIN orders o ON o.id = oi.order_id WHERE o.status = 'paid' GROUP BY oi.book_id) s ON s.book_id = b.id";
}

/**
 * 스토어 목록. $opts: category, q, sort(latest|new|best), page, per_page
 * 반환: [목록, 전체 개수]
 */
function find_books($opts)
{
    $where = array("b.status = 'on_sale'");
    $params = array();
    if (!empty($opts['category'])) {
        $where[] = 'b.category = ?';
        $params[] = $opts['category'];
    }
    if (!empty($opts['q'])) {
        // SQLite·MySQL 공통으로 쓰려고 이스케이프 문자로 ! 를 씁니다.
        $where[] = "(b.title LIKE ? ESCAPE '!' OR b.author LIKE ? ESCAPE '!')";
        $like = '%' . str_replace(array('!', '%', '_'), array('!!', '!%', '!_'), $opts['q']) . '%';
        $params[] = $like;
        $params[] = $like;
    }
    $sort = $opts['sort'] ?? 'latest';
    if ($sort === 'new') {
        $where[] = 'b.published_at >= ?';
        $params[] = date('Y-m-d H:i:s', strtotime('-90 days'));
    }
    $order = $sort === 'best' ? 'sold DESC, avg_rating DESC, b.published_at DESC' : 'b.published_at DESC, b.id DESC';

    $whereSql = ' WHERE ' . implode(' AND ', $where);
    $total = (int) q_value('SELECT COUNT(*) FROM books b' . $whereSql, $params);
    $perPage = (int) ($opts['per_page'] ?? 12);
    $offset = max(0, ((int) ($opts['page'] ?? 1) - 1) * $perPage);
    $rows = q_all(book_select_sql() . $whereSql . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset, $params);
    return array($rows, $total);
}

function find_book($id)
{
    return q_one(book_select_sql() . ' WHERE b.id = ?', array((int) $id));
}

function book_on_sale($book)
{
    return $book && $book['status'] === 'on_sale';
}

function book_preview_images($book)
{
    $list = json_decode((string) $book['preview_images'], true);
    return is_array($list) ? $list : array();
}

function book_has_preview($book)
{
    if ($book['preview_mode'] === 'manual') {
        return trim((string) $book['preview_text']) !== '';
    }
    return trim((string) $book['preview_html']) !== '' || book_preview_images($book);
}

/* ───────── 소유 · 장바구니 ───────── */

function owned_book_ids($userId)
{
    $rows = q_all("SELECT DISTINCT oi.book_id FROM order_items oi JOIN orders o ON o.id = oi.order_id
        WHERE o.user_id = ? AND o.status = 'paid'", array((int) $userId));
    return array_map('intval', array_column($rows, 'book_id'));
}

/** 입금 대기 중인 주문에 들어 있는 책 → 주문번호 */
function pending_book_orders($userId)
{
    $rows = q_all("SELECT oi.book_id, o.order_no FROM order_items oi JOIN orders o ON o.id = oi.order_id
        WHERE o.user_id = ? AND o.status = 'pending'", array((int) $userId));
    $map = array();
    foreach ($rows as $r) {
        $map[(int) $r['book_id']] = $r['order_no'];
    }
    return $map;
}

function user_owns_book($userId, $bookId)
{
    return in_array((int) $bookId, owned_book_ids($userId), true);
}

function cart_ids()
{
    if (!has_session()) {
        return array();
    }
    return array_values(array_map('intval', $_SESSION['cart'] ?? array()));
}

function cart_add($bookId)
{
    start_session();
    $ids = cart_ids();
    if (!in_array((int) $bookId, $ids, true)) {
        $ids[] = (int) $bookId;
    }
    $_SESSION['cart'] = $ids;
}

function cart_remove($bookIds)
{
    start_session();
    $_SESSION['cart'] = array_values(array_diff(cart_ids(), array_map('intval', (array) $bookIds)));
}

function cart_count()
{
    return count(cart_ids());
}

/** 장바구니(또는 바로구매) 책들을 불러오고, 살 수 없는 책은 이유와 함께 표시합니다. */
function load_checkout_books($ids, $user)
{
    if (!$ids) {
        return array();
    }
    $owned = $user ? owned_book_ids($user['id']) : array();
    $pending = $user ? pending_book_orders($user['id']) : array();
    $books = array();
    foreach ($ids as $id) {
        $b = find_book($id);
        if (!$b) {
            continue;
        }
        $b['blocked'] = '';
        if (!book_on_sale($b)) {
            $b['blocked'] = '판매가 중단된 책이에요.';
        } elseif (in_array((int) $b['id'], $owned, true)) {
            $b['blocked'] = '이미 구매한 책이에요.';
        } elseif (isset($pending[(int) $b['id']])) {
            $b['blocked'] = '입금 대기 중인 주문에 들어 있어요.';
        }
        $books[] = $b;
    }
    return $books;
}

/* ───────── 주문 (무통장 입금) ───────── */

const ORDER_STATUS = array('pending' => '입금 대기', 'paid' => '결제 완료', 'cancelled' => '주문 취소');

function generate_order_no()
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $no = date('ymd') . '-' . $code;
    } while (q_value('SELECT id FROM orders WHERE order_no = ?', array($no)));
    return $no;
}

/** 주문을 만들고 입금 계좌를 주문에 함께 기록합니다(나중에 계좌를 바꿔도 주문 안내는 그대로). */
function create_order($user, $books, $depositor)
{
    $total = 0;
    foreach ($books as $b) {
        $total += (int) $b['price'];
    }
    $days = max(1, (int) setting('deposit_days'));
    db()->beginTransaction();
    try {
        $orderId = q_insert('orders', array(
            'order_no' => generate_order_no(),
            'user_id' => (int) $user['id'],
            'depositor' => $depositor,
            'total' => $total,
            'status' => 'pending',
            'bank_name' => setting('bank_name'),
            'bank_account' => setting('bank_account'),
            'bank_holder' => setting('bank_holder'),
            'due_at' => date('Y-m-d 23:59:59', strtotime('+' . $days . ' days')),
            'created_at' => now(),
        ));
        foreach ($books as $b) {
            // 회원이 올린 책이면 판매 시점의 수수료율과 판매자 몫을 함께 적어 둡니다.
            $sellerId = !empty($b['seller_user_id']) ? (int) $b['seller_user_id'] : null;
            $rate = $sellerId ? seller_commission() : 0;
            q_insert('order_items', array(
                'order_id' => $orderId, 'book_id' => (int) $b['id'], 'title' => $b['title'], 'price' => (int) $b['price'],
                'seller_user_id' => $sellerId, 'commission_rate' => $rate,
                'seller_amount' => $sellerId ? (int) $b['price'] - intdiv((int) $b['price'] * $rate, 100) : 0,
            ));
        }
        db()->commit();
    } catch (Exception $e) {
        db()->rollBack();
        throw $e;
    }
    return q_one('SELECT * FROM orders WHERE id = ?', array($orderId));
}

function find_order_by_no($no)
{
    return q_one('SELECT * FROM orders WHERE order_no = ?', array((string) $no));
}

function order_items($orderId)
{
    return q_all('SELECT oi.*, b.cover_path, b.author, b.category, b.file_format, b.id AS book_exists
        FROM order_items oi LEFT JOIN books b ON b.id = oi.book_id WHERE oi.order_id = ? ORDER BY oi.id', array((int) $orderId));
}

function user_orders($userId)
{
    return q_all('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC', array((int) $userId));
}

function set_order_status($orderId, $status)
{
    $row = array('status' => $status);
    if ($status === 'paid') {
        $row['paid_at'] = now();
        $row['cancelled_at'] = null;
    } elseif ($status === 'cancelled') {
        $row['cancelled_at'] = now();
        $row['paid_at'] = null;
    } else {
        $row['paid_at'] = null;
        $row['cancelled_at'] = null;
    }
    q_update('orders', (int) $orderId, $row);
}

/* ───────── 리뷰 ───────── */

const REVIEW_SORTS = array('latest' => '최신순', 'rating' => '평점 높은순', 'helpful' => '도움순');

function rating_summary($bookId)
{
    $rows = q_all("SELECT rating, COUNT(*) AS n FROM reviews WHERE book_id = ? AND status = 'visible' GROUP BY rating", array((int) $bookId));
    $dist = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
    $count = 0;
    $sum = 0;
    foreach ($rows as $r) {
        $dist[(int) $r['rating']] = (int) $r['n'];
        $count += (int) $r['n'];
        $sum += (int) $r['rating'] * (int) $r['n'];
    }
    return array('avg' => $count ? $sum / $count : 0, 'count' => $count, 'dist' => $dist);
}

function book_reviews($bookId, $sort, $limit, $userId)
{
    $order = array(
        'latest' => 'r.id DESC',
        'rating' => 'r.rating DESC, r.id DESC',
        'helpful' => 'r.helpful DESC, r.id DESC',
    );
    $sql = "SELECT r.*, u.name AS user_name,
            (SELECT COUNT(*) FROM review_votes v WHERE v.review_id = r.id AND v.user_id = ?) AS voted
        FROM reviews r JOIN users u ON u.id = r.user_id
        WHERE r.book_id = ? AND r.status = 'visible'
        ORDER BY " . ($order[$sort] ?? $order['latest']) . ' LIMIT ' . (int) $limit;
    return q_all($sql, array((int) $userId, (int) $bookId));
}

function user_review($bookId, $userId)
{
    return q_one('SELECT * FROM reviews WHERE book_id = ? AND user_id = ?', array((int) $bookId, (int) $userId));
}

/* ───────── 뷰어 · 읽던 위치 ───────── */

function downloads_allowed()
{
    return setting('allow_download') === '1';
}

/** 읽던 위치: ['c' => 장, 'r' => 장 안 비율] (EPUB) 또는 ['p' => 쪽] (PDF), 'percent' 포함. 없으면 null */
function reading_progress($userId, $bookId)
{
    $row = q_one('SELECT position, percent FROM reading_progress WHERE user_id = ? AND book_id = ?', array((int) $userId, (int) $bookId));
    if (!$row) {
        return null;
    }
    $pos = json_decode($row['position'], true);
    $pos = is_array($pos) ? $pos : array();
    $pos['percent'] = (int) $row['percent'];
    return $pos;
}

function save_reading_progress($userId, $bookId, $position, $percent)
{
    q('DELETE FROM reading_progress WHERE user_id = ? AND book_id = ?', array((int) $userId, (int) $bookId));
    q_insert('reading_progress', array(
        'user_id' => (int) $userId, 'book_id' => (int) $bookId, 'position' => json_encode($position),
        'percent' => max(0, min(100, (int) $percent)), 'updated_at' => now(),
    ));
}

/** 회원의 책별 읽은 비율(%) */
function reading_percents($userId)
{
    $map = array();
    foreach (q_all('SELECT book_id, percent FROM reading_progress WHERE user_id = ?', array((int) $userId)) as $r) {
        $map[(int) $r['book_id']] = (int) $r['percent'];
    }
    return $map;
}

/* ───────── 오픈마켓 설정 ───────── */

function seller_enabled()
{
    return setting('seller_enabled') === '1';
}

/** 판매 수수료(%) 0~90 */
function seller_commission()
{
    return max(0, min(90, (int) setting('seller_commission')));
}

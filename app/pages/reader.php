<?php
/**
 * 전자책 뷰어: 구매한 책(입금 확인)을 사이트 안에서 읽습니다. 관리자는 모든 책을 볼 수 있어요.
 *   EPUB: 서버가 장(chapter)마다 본문을 정리해 사이트 글꼴로 보여 줍니다(기기별 글자 크기).
 *   PDF : 브라우저(pdf.js)가 화면 폭에 맞춰 쪽을 그립니다.
 * 읽던 위치는 회원별로 저장해서 다른 기기에서도 이어 읽을 수 있어요.
 */

/** 볼 수 있는 사람인지 확인. $page=true 면 로그인·구매 화면으로 보내고, 아니면 403/404 로 끝냅니다. */
function reader_access($id, $page)
{
    $book = find_book($id);
    if (!$book) {
        $page ? not_found() : json_out(array('error' => 'not found'), 404);
    }
    $admin = current_admin();
    $user = current_user();
    if ($admin) {
        return array($book, $user);
    }
    if (!$user) {
        if (!$page) {
            json_out(array('error' => '로그인이 필요해요.'), 401);
        }
        flash('로그인하면 구매한 책을 바로 읽을 수 있어요.', 'info');
        redirect('/login?next=' . rawurlencode('/read/' . (int) $book['id']));
    }
    $isSeller = (int) ($book['seller_user_id'] ?? 0) === (int) $user['id'];
    if (!$isSeller && !user_owns_book($user['id'], $book['id'])) {
        if (!$page) {
            json_out(array('error' => '구매한 책만 읽을 수 있어요.'), 403);
        }
        flash('구매한 책만 뷰어로 읽을 수 있어요. 입금이 확인되면 바로 열려요.', 'info');
        redirect('/books/' . (int) $book['id']);
    }
    return array($book, $user);
}

function page_reader($id)
{
    list($book, $user) = reader_access($id, true);
    $path = book_file_path($book);
    if ($path === '' || !is_file($path)) {
        flash('책 파일을 찾을 수 없어요. 고객센터로 문의해 주세요.', 'error');
        redirect('/books/' . (int) $book['id']);
    }
    $progress = $user ? reading_progress($user['id'], $book['id']) : null;
    $vars = array(
        'title' => $book['title'],
        'book' => $book,
        'user' => $user,
        'format' => $book['file_format'],
        'back' => $user ? '/library' : '/admin/books',
    );

    if ($book['file_format'] === 'EPUB') {
        $epub = epub_open($path);
        if (!$epub) {
            flash('책 파일을 열 수 없어요. 고객센터로 문의해 주세요.', 'error');
            redirect('/books/' . (int) $book['id']);
        }
        $chapters = epub_chapters($epub, $path);
        $count = count($chapters);
        $c = input_int('c', 0);
        $restore = null;
        if ($c < 1) {
            // 장을 고르지 않고 들어오면 읽던 곳부터 이어 읽습니다.
            $c = $progress && isset($progress['c']) ? (int) $progress['c'] : 1;
            $restore = $progress && isset($progress['r']) ? (float) $progress['r'] : null;
        }
        $c = max(1, min($count, $c));
        $vars += array(
            'chapters' => $chapters,
            'c' => $c,
            'html' => epub_chapter_html($epub, $c - 1, $book['id']),
            'restore' => $restore,
        );
        $epub['zip']->close();
    } else {
        $vars += array('page' => $progress && isset($progress['p']) ? max(1, (int) $progress['p']) : 1);
    }
    render('reader', $vars, null);
}

/** PDF 원본(뷰어가 읽어 가는 용도). 구매자·관리자만. */
function action_reader_file($id)
{
    list($book) = reader_access($id, false);
    $path = book_file_path($book);
    if ($book['file_format'] !== 'PDF' || $path === '' || !is_file($path)) {
        json_out(array('error' => 'not found'), 404);
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline');
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

/** EPUB 안의 그림. 구매자·관리자만. */
function action_reader_asset($id)
{
    list($book) = reader_access($id, false);
    $target = input('p');
    $types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml');
    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    $epub = $book['file_format'] === 'EPUB' ? epub_open(book_file_path($book)) : null;
    $data = ($epub && isset($types[$ext]) && strpos($target, '..') === false) ? $epub['zip']->getFromName($target) : false;
    if ($epub) {
        $epub['zip']->close();
    }
    if ($data === false) {
        json_out(array('error' => 'not found'), 404);
    }
    header('Content-Type: ' . $types[$ext]);
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: private, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    // 그림 파일을 직접 열어도 스크립트가 실행되지 않게 막습니다(SVG 대비).
    header("Content-Security-Policy: sandbox; default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'");
    echo $data;
    exit;
}

/** 읽던 위치 저장(뷰어가 스크롤할 때 보냅니다). */
function action_reader_progress($id)
{
    list($book, $user) = reader_access($id, false);
    if (!$user) {
        json_out(array('ok' => true));
    }
    if (!csrf_valid()) {
        json_out(array('error' => 'csrf'), 400);
    }
    if ($book['file_format'] === 'EPUB') {
        $position = array('c' => max(1, input_int('c', 1)), 'r' => round(max(0, min(1, (float) input('r'))), 4));
    } else {
        $position = array('p' => max(1, input_int('p', 1)));
    }
    save_reading_progress($user['id'], $book['id'], $position, (int) round((float) input('pct')));
    json_out(array('ok' => true));
}

<?php
/**
 * 오픈마켓(판매자): 회원이 자기 전자책을 올리고(관리자 승인 후 판매), 판매 내역을 보고, 판매 정산을 출금 신청합니다.
 * 관리자 › 설정 › 오픈마켓에서 켜야 보여요. 판매 수수료도 거기서 정해요.
 */

function seller_user()
{
    if (!seller_enabled()) {
        not_found();
    }
    return require_user('/market/sell');
}

/** 내 책만 불러옵니다. */
function seller_own_book($userId, $id)
{
    $book = q_one('SELECT * FROM books WHERE id = ? AND seller_user_id = ?', array((int) $id, (int) $userId));
    if (!$book) {
        not_found();
    }
    return $book;
}

function page_seller_home()
{
    $user = seller_user();
    $account = seller_account($user['id']);

    if (is_post()) {
        require_csrf('/market/sell');
        $action = input('action');
        if ($action === 'bank') {
            $bank = array('bank_name' => str_cut(input('bank_name'), 30, ''), 'bank_account' => str_cut(input('bank_account'), 40, ''), 'bank_holder' => str_cut(input('bank_holder'), 30, ''));
            if (in_array('', $bank, true)) {
                flash('은행, 계좌번호, 예금주를 모두 입력해 주세요.', 'error');
            } else {
                if ($account) {
                    q('UPDATE sellers SET bank_name = ?, bank_account = ?, bank_holder = ? WHERE user_id = ?', array($bank['bank_name'], $bank['bank_account'], $bank['bank_holder'], (int) $user['id']));
                } else {
                    q_insert('sellers', $bank + array('user_id' => (int) $user['id'], 'created_at' => now()));
                }
                flash('정산 계좌를 저장했어요.');
            }
        } elseif ($action === 'withdraw') {
            $amount = input_int('amount');
            $balance = seller_balance($user['id']);
            if (!$account || $account['bank_account'] === '') {
                flash('정산 계좌를 먼저 저장해 주세요.', 'error');
            } elseif ($amount < WITHDRAW_MIN) {
                flash('정산은 ' . won(WITHDRAW_MIN) . '부터 신청할 수 있어요.', 'error');
            } elseif ($amount > $balance['available']) {
                flash('정산 가능 금액(' . won($balance['available']) . ')보다 많아요.', 'error');
            } else {
                q_insert('withdrawals', array(
                    'user_id' => (int) $user['id'], 'amount' => $amount, 'kind' => 'seller',
                    'bank_name' => $account['bank_name'], 'bank_account' => $account['bank_account'], 'bank_holder' => $account['bank_holder'],
                    'status' => 'requested', 'admin_memo' => '', 'created_at' => now(),
                ));
                flash(won($amount) . ' 정산을 신청했어요. 관리자가 확인한 뒤 계좌로 보내 드려요.');
            }
        }
        redirect('/market/sell');
    }

    render('seller_home', array(
        'title' => '내 전자책 판매',
        'nav' => 'market',
        'user' => $user,
        'account' => $account,
        'books' => seller_books($user['id']),
        'sales' => seller_sales($user['id']),
        'balance' => seller_balance($user['id']),
        'withdrawals' => user_withdrawals($user['id'], 'seller'),
    ));
}

function seller_book_form($id = null)
{
    $user = seller_user();
    $book = $id ? seller_own_book($user['id'], $id) : null;
    book_form_page($book, array(
        'mode' => 'seller',
        'seller_id' => (int) $user['id'],
        'urls' => array(
            'action' => $book ? '/market/sell/' . (int) $book['id'] . '/edit' : '/market/sell/new',
            'list' => '/market/sell',
            'edit' => '/market/sell/%d/edit',
            'file' => $book ? '/market/sell/' . (int) $book['id'] . '/file' : '',
            'delete' => $book ? '/market/sell/' . (int) $book['id'] . '/delete' : '',
        ),
    ));
}

function seller_book_delete($id)
{
    $user = seller_user();
    $book = seller_own_book($user['id'], $id);
    require_csrf('/market/sell/' . (int) $book['id'] . '/edit');
    if (q_value('SELECT COUNT(*) FROM order_items WHERE book_id = ?', array($book['id']))) {
        flash('주문 기록이 있는 책은 지울 수 없어요. 관리자에게 판매 중지를 요청해 주세요.', 'error');
        redirect('/market/sell/' . (int) $book['id'] . '/edit');
    }
    q('DELETE FROM review_votes WHERE review_id IN (SELECT id FROM reviews WHERE book_id = ?)', array($book['id']));
    q('DELETE FROM reviews WHERE book_id = ?', array($book['id']));
    q('DELETE FROM books WHERE id = ?', array($book['id']));
    if (book_file_path($book) !== '') {
        @unlink(book_file_path($book));
    }
    delete_public_file($book['cover_path']);
    delete_preview_images($book['id']);
    flash('‘' . $book['title'] . '’을(를) 지웠어요.');
    redirect('/market/sell');
}

/** PDF 미리보기를 다시 만들 때 내 원본을 읽어 갑니다. */
function seller_book_file($id)
{
    $user = seller_user();
    $book = seller_own_book($user['id'], $id);
    $path = book_file_path($book);
    if ($path === '' || !is_file($path)) {
        not_found();
    }
    header('Content-Type: ' . ($book['file_format'] === 'PDF' ? 'application/pdf' : 'application/epub+zip'));
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

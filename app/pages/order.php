<?php
/**
 * 장바구니와 무통장 입금 주문.
 * 흐름: 장바구니/바로구매 → 주문서(입금자명) → 주문 완료(입금 계좌 안내) → 관리자 입금 확인 → 내 서재에서 다운로드
 */

function page_cart()
{
    $user = current_user();
    $books = load_checkout_books(cart_ids(), $user);
    render('cart', array('title' => '장바구니', 'books' => $books));
}

function action_cart_add()
{
    $bookId = input_int('book_id');
    $back = '/books/' . $bookId;
    require_csrf($back);
    $book = find_book($bookId);
    if (!book_on_sale($book)) {
        flash('지금은 구매할 수 없는 책이에요.', 'error');
        redirect('/');
    }
    if (book_is_free($book)) {
        flash('무료 책은 결제 없이 ‘무료로 읽기’로 바로 받을 수 있어요.', 'info');
        redirect($back);
    }
    $user = current_user();
    if ($user && user_owns_book($user['id'], $bookId)) {
        flash('이미 구매한 책이에요. 내 서재에서 내려받을 수 있어요.', 'info');
        redirect($back);
    }
    if (input('buy_now') === '1') {
        redirect('/checkout?buy=' . $bookId);
    }
    cart_add($bookId);
    flash('장바구니에 담았어요.');
    redirect($back);
}

/** 무료 책 받기(관리자가 올린 0원 책). 로그인한 회원만, 받은 뒤 바로 뷰어로. */
function action_book_free($id)
{
    $back = '/books/' . (int) $id;
    require_csrf($back);
    $book = find_book($id);
    if (!book_on_sale($book) || !book_is_free($book)) {
        flash('무료로 받을 수 없는 책이에요.', 'error');
        redirect($book ? $back : '/');
    }
    $user = current_user();
    if (!$user) {
        flash('로그인하면 무료 책을 바로 읽을 수 있어요. 회원가입도 무료예요.', 'info');
        redirect('/login?next=' . rawurlencode($back));
    }
    if (!user_owns_book($user['id'], $book['id'])) {
        claim_free_book($user, $book);
        flash('내 서재에 담았어요. 언제든 이어서 읽을 수 있어요.');
    }
    redirect('/read/' . (int) $book['id']);
}

function action_cart_remove()
{
    require_csrf('/cart');
    cart_remove(input_int('book_id'));
    redirect('/cart');
}

function page_checkout()
{
    $user = require_user();
    $buy = input_int('buy');
    $ids = $buy ? array($buy) : cart_ids();
    $books = load_checkout_books($ids, $user);
    if (!$books) {
        flash('주문할 책을 먼저 담아 주세요.', 'info');
        redirect('/cart');
    }
    $buyable = array_values(array_filter($books, function ($b) {
        return $b['blocked'] === '';
    }));
    $total = array_sum(array_map(function ($b) {
        return (int) $b['price'];
    }, $buyable));

    $errors = array();
    $depositor = input('depositor', $user['name']);
    if (is_post()) {
        if (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        }
        if (!bank_ready()) {
            $errors[] = '입금 계좌가 아직 준비되지 않았어요. 잠시 뒤에 다시 시도해 주세요.';
        }
        if (!$buyable) {
            $errors[] = '주문할 수 있는 책이 없어요.';
        }
        if ($depositor === '' || str_len($depositor) > 30) {
            $errors[] = '입금자명을 30자 이내로 입력해 주세요.';
        }
        if (input('agree') !== '1') {
            $errors[] = '주문 내용과 청약철회 안내에 동의해 주세요.';
        }
        if (!$errors) {
            $order = create_order($user, $buyable, $depositor);
            cart_remove(array_column($books, 'id'));
            redirect('/orders/' . $order['order_no'] . '?new=1');
        }
    }
    render('checkout', array(
        'title' => '주문하기',
        'user' => $user,
        'books' => $books,
        'buyable' => $buyable,
        'total' => $total,
        'depositor' => $depositor,
        'buy' => $buy,
        'errors' => $errors,
    ));
}

/** 주문 조회: 주문한 회원 본인(또는 관리자)만 볼 수 있습니다. */
function load_own_order($no)
{
    $order = find_order_by_no($no);
    if (current_admin() && $order) {
        return $order;
    }
    $user = require_user();
    if (!$order || (int) $order['user_id'] !== (int) $user['id']) {
        not_found();
    }
    return $order;
}

function page_order($no)
{
    $order = load_own_order($no);
    render('order', array(
        'title' => '주문 ' . $order['order_no'],
        'order' => $order,
        'items' => order_items($order['id']),
        'isNew' => input('new') === '1',
    ));
}

function action_order_cancel($no)
{
    $order = load_own_order($no);
    require_csrf('/orders/' . $order['order_no']);
    if ($order['status'] === 'pending') {
        set_order_status($order['id'], 'cancelled');
        flash('주문을 취소했어요.');
    }
    redirect('/orders/' . $order['order_no']);
}

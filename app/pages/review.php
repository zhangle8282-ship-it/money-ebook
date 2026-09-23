<?php
/**
 * 리뷰: 작성(구매한 회원만, 책마다 1개), 도움돼요, 내 리뷰 삭제.
 */

function review_back($bookId)
{
    return safe_back(input('back'), '/books/' . (int) $bookId) . '#reviews';
}

function action_review_create($bookId)
{
    $back = review_back($bookId);
    $user = require_user($back);
    require_csrf($back);
    $book = find_book($bookId);
    if (!$book) {
        not_found();
    }
    $rating = input_int('rating');
    $body = trim(str_replace("\r\n", "\n", input('body')));
    if (!user_owns_book($user['id'], $book['id'])) {
        flash('구매한 분만 리뷰를 쓸 수 있어요.', 'error');
    } elseif (user_review($book['id'], $user['id'])) {
        flash('이 책에는 이미 리뷰를 남겼어요.', 'info');
    } elseif ($rating < 1 || $rating > 5) {
        flash('별점을 선택해 주세요.', 'error');
    } elseif (str_len($body) < 5 || str_len($body) > 1000) {
        flash('리뷰 내용은 5자 이상 1000자 이내로 써 주세요.', 'error');
    } else {
        q_insert('reviews', array(
            'book_id' => (int) $book['id'], 'user_id' => (int) $user['id'], 'rating' => $rating,
            'body' => $body, 'helpful' => 0, 'status' => 'visible', 'created_at' => now(),
        ));
        flash('리뷰를 등록했어요. 고마워요!');
    }
    redirect($back);
}

function action_review_helpful($reviewId)
{
    $review = q_one("SELECT * FROM reviews WHERE id = ? AND status = 'visible'", array((int) $reviewId));
    if (!$review) {
        not_found();
    }
    $back = review_back($review['book_id']);
    $user = require_user($back);
    require_csrf($back);
    if ((int) $review['user_id'] === (int) $user['id']) {
        flash('내가 쓴 리뷰에는 누를 수 없어요.', 'info');
        redirect($back);
    }
    $voted = q_value('SELECT COUNT(*) FROM review_votes WHERE review_id = ? AND user_id = ?', array($review['id'], $user['id']));
    if ($voted) {
        q('DELETE FROM review_votes WHERE review_id = ? AND user_id = ?', array($review['id'], $user['id']));
    } else {
        q_insert('review_votes', array('review_id' => (int) $review['id'], 'user_id' => (int) $user['id']));
    }
    q('UPDATE reviews SET helpful = (SELECT COUNT(*) FROM review_votes WHERE review_id = ?) WHERE id = ?', array($review['id'], $review['id']));
    redirect($back);
}

function action_review_delete($reviewId)
{
    $user = require_user();
    $review = q_one('SELECT * FROM reviews WHERE id = ?', array((int) $reviewId));
    if (!$review || (int) $review['user_id'] !== (int) $user['id']) {
        not_found();
    }
    $back = review_back($review['book_id']);
    require_csrf($back);
    q('DELETE FROM review_votes WHERE review_id = ?', array($review['id']));
    q('DELETE FROM reviews WHERE id = ?', array($review['id']));
    flash('리뷰를 삭제했어요.');
    redirect($back);
}

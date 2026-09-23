<?php
/**
 * 스토어 화면: 홈(목록·검색), 전자책 상세, 약관 페이지.
 */

const HOME_SORTS = array(
    'latest' => array('nav' => '전체', 'title' => '새로 나온 전자책', 'hint' => '최신순'),
    'new' => array('nav' => '신간', 'title' => '신간', 'hint' => '최근 90일'),
    'best' => array('nav' => '베스트', 'title' => '베스트', 'hint' => '판매순'),
);

function page_home()
{
    $sort = input('sort');
    if (!array_key_exists($sort, HOME_SORTS)) {
        $sort = 'latest';
    }
    $category = input('cat');
    $q = str_cut(input('q'), 50, '');
    $page = max(1, input_int('page', 1));
    $perPage = 12;
    list($books, $total) = find_books(array(
        'category' => $category, 'q' => $q, 'sort' => $sort, 'page' => $page, 'per_page' => $perPage,
    ));

    if ($q !== '') {
        $heading = '‘' . $q . '’ 검색 결과';
        $hint = $total . '권';
    } else {
        $heading = $category !== '' ? $category : HOME_SORTS[$sort]['title'];
        $hint = HOME_SORTS[$sort]['hint'];
    }
    render('home', array(
        'title' => $q !== '' ? $heading : ($category !== '' ? $category : ''),
        'nav' => $q === '' ? $sort : '',
        'sort' => $sort,
        'category' => $category,
        'q' => $q,
        'books' => $books,
        'total' => $total,
        'page' => $page,
        'pages' => max(1, (int) ceil($total / $perPage)),
        'heading' => $heading,
        'hint' => $hint,
    ));
}

function page_book($id)
{
    $book = find_book($id);
    if (!$book || (!book_on_sale($book) && !current_admin())) {
        not_found();
    }
    $user = current_user();
    $owned = $user && user_owns_book($user['id'], $book['id']);
    $pending = $user ? pending_book_orders($user['id']) : array();

    $sort = input('sort');
    if (!array_key_exists($sort, REVIEW_SORTS)) {
        $sort = 'latest';
    }
    $limit = min(200, max(5, input_int('rv', 5)));
    $summary = rating_summary($book['id']);
    $myReview = $user ? user_review($book['id'], $user['id']) : null;

    render('book', array(
        'title' => $book['title'],
        'description' => str_cut(trim(preg_replace('/\s+/u', ' ', (string) $book['description'])), 120),
        'book' => $book,
        'user' => $user,
        'owned' => $owned,
        'pendingNo' => $pending[(int) $book['id']] ?? null,
        'inCart' => in_array((int) $book['id'], cart_ids(), true),
        'summary' => $summary,
        'sort' => $sort,
        'limit' => $limit,
        'reviews' => book_reviews($book['id'], $sort, $limit, $user ? $user['id'] : 0),
        'myReview' => $myReview,
        'canReview' => $user && $owned && !$myReview,
    ));
}

function page_static($slug)
{
    $pages = array('terms' => array('이용약관', 'terms_text'), 'privacy' => array('개인정보처리방침', 'privacy_text'));
    render('static', array('title' => $pages[$slug][0], 'body' => setting($pages[$slug][1])));
}

<?php
/**
 * 그린청소 블로그: 공개 화면(/blog, /blog/번호-글이름, /rss.xml)과 관리자(글 목록·쓰기·사진 올리기).
 */

function page_blog_list()
{
    $page = max(1, input_int('page', 1));
    list($posts, $total) = blog_page($page);
    $pages = max(1, (int) ceil($total / BLOG_PER_PAGE));
    if ($page > $pages && $total > 0) {
        not_found();
    }
    render('cleaning/blog_list', array('posts' => $posts, 'page' => $page, 'pages' => $pages, 'total' => $total), null);
}

function page_blog_post($id, $slug = '')
{
    $post = find_blog_post($id);
    $admin = current_admin();
    if (!$post || (!blog_is_public($post) && !$admin)) {
        not_found();
    }
    // 글 이름이 바뀌었거나 빠진 주소로 들어오면 대표 주소로 옮겨 검색 점수가 한곳에 모이게 합니다.
    if (rawurldecode((string) $slug) !== $post['slug'] && blog_is_public($post)) {
        header('Location: ' . blog_url($post), true, 301);
        exit;
    }
    if (!$admin) {
        q('UPDATE blog_posts SET views = views + 1 WHERE id = ?', array((int) $post['id']));
    }
    render('cleaning/blog_post', array(
        'post' => $post,
        'related' => blog_latest(3, (int) $post['id']),
        'preview' => !blog_is_public($post),
    ), null);
}

function blog_rss()
{
    header('Content-Type: application/rss+xml; charset=utf-8');
    $name = gc('name');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'
        . '<title>' . e($name . ' 블로그') . '</title>'
        . '<link>' . e(base_url() . '/blog') . '</link>'
        . '<atom:link href="' . e(base_url() . '/rss.xml') . '" rel="self" type="application/rss+xml"/>'
        . '<description>' . e($name . ' – 충북 음성·진천·혁신도시 사무실·상가·공장·화장실 청소 이야기') . '</description>'
        . '<language>ko</language>';
    foreach (blog_latest(30) as $p) {
        echo '<item><title>' . e($p['title']) . '</title>'
            . '<link>' . e(blog_url($p, true)) . '</link>'
            . '<guid isPermaLink="true">' . e(blog_url($p, true)) . '</guid>'
            . '<pubDate>' . e(date('r', strtotime($p['published_at']))) . '</pubDate>'
            . '<description>' . e(blog_desc($p, 200)) . '</description></item>';
    }
    echo '</channel></rss>';
    exit;
}

/* ───────── 관리자 ───────── */

function admin_blog_list()
{
    require_admin();
    render_admin('blog_list', array(
        'title' => '블로그', 'nav' => 'blog',
        'posts' => q_all('SELECT * FROM blog_posts ORDER BY COALESCE(published_at, created_at) DESC, id DESC'),
    ));
}

function admin_blog_form($id = null)
{
    require_admin();
    $post = $id !== null ? find_blog_post($id) : null;
    if ($id !== null && !$post) {
        not_found();
    }
    $form = $post ?: array(
        'title' => '', 'slug' => '', 'summary' => '', 'body' => '', 'cover' => '', 'seo_title' => '', 'keywords' => '',
        'status' => 'published', 'published_at' => null,
    );
    $errors = array();
    $here = $post ? '/admin/blog/' . (int) $post['id'] . '/edit' : '/admin/blog/new';
    if (is_post() && !$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = '올린 사진이 서버 한도(post_max_size ' . ini_get('post_max_size') . ')보다 커요.';
    } elseif (is_post()) {
        require_csrf($here);
        $status = input('status') === 'draft' ? 'draft' : 'published';
        $date = input('published_date');
        $form = array_merge($form, array(
            'title' => str_cut(trim(preg_replace('/\s+/u', ' ', input('title'))), 200, ''),
            'body' => str_replace("\r\n", "\n", (string) input('body')),
            'summary' => str_cut(trim(preg_replace('/\s+/u', ' ', input('summary'))), 300, ''),
            'seo_title' => str_cut(trim(input('seo_title')), 200, ''),
            'keywords' => str_cut(trim(input('keywords')), 300, ''),
            'slug' => blog_slugify(input('slug') !== '' ? input('slug') : input('title')),
            'status' => $status,
        ));
        if ($form['title'] === '') {
            $errors[] = '글 제목을 적어 주세요.';
        }
        if ($status === 'published' && str_len(blog_plain($form['body'])) < 20) {
            $errors[] = '공개하려면 본문을 조금 더 써 주세요(20자 이상). 아직 쓰는 중이면 ‘임시저장’으로 저장하세요.';
        }
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = '작성일을 확인해 주세요.';
        }
        // 공개 시각: 고른 날짜(오늘이면 지금 시각), 비우면 처음 공개할 때 지금
        if ($status === 'published') {
            if ($date !== '' && $date !== date('Y-m-d')) {
                $form['published_at'] = $date . ' 09:00:00';
            } elseif ($date === date('Y-m-d') && $post && $post['published_at'] && substr($post['published_at'], 0, 10) === $date) {
                $form['published_at'] = $post['published_at'];
            } else {
                $form['published_at'] = $form['published_at'] ?: now();
                if ($date === date('Y-m-d') && substr((string) $form['published_at'], 0, 10) !== $date) {
                    $form['published_at'] = now();
                }
            }
        } elseif ($date !== '') {
            $form['published_at'] = $date . ' 09:00:00';
        }
        if (!$errors) {
            try {
                if (input('remove_cover') === '1') {
                    delete_public_file($form['cover']);
                    $form['cover'] = '';
                }
                if (has_upload('cover')) {
                    $path = store_image($_FILES['cover'], 'blog');
                    delete_public_file($form['cover']);
                    $form['cover'] = $path;
                }
            } catch (RuntimeException $e) {
                $errors[] = '대표 사진: ' . $e->getMessage();
            }
        }
        if (!$errors) {
            $data = array_intersect_key($form, array_flip(array('title', 'slug', 'summary', 'body', 'cover', 'seo_title', 'keywords', 'status', 'published_at')));
            $data['updated_at'] = now();
            if ($post) {
                q_update('blog_posts', (int) $post['id'], $data);
                $newId = (int) $post['id'];
            } else {
                $data['created_at'] = now();
                $newId = q_insert('blog_posts', $data);
            }
            flash($status === 'published' ? '글을 저장하고 공개했어요. 검색 사이트에는 보통 며칠 안에 반영돼요.' : '임시저장했어요. 공개하려면 ‘공개’로 바꿔 저장하세요.');
            redirect('/admin/blog/' . $newId . '/edit');
        }
    }
    render_admin('blog_form', array('title' => $post ? '글 고치기' : '새 글 쓰기', 'nav' => 'blog', 'post' => $post, 'form' => $form, 'errors' => $errors));
}

function admin_blog_delete($id)
{
    require_admin();
    require_csrf('/admin/blog');
    $post = find_blog_post($id);
    if (!$post) {
        not_found();
    }
    q('DELETE FROM blog_posts WHERE id = ?', array((int) $post['id']));
    delete_public_file($post['cover']);
    flash('‘' . $post['title'] . '’ 글을 지웠어요.');
    redirect('/admin/blog');
}

/** 본문에 넣을 사진 올리기(글쓰기 화면의 ‘사진 넣기’). 반환: {"ok":true,"path":"/uploads/blog/…"} */
function admin_blog_upload()
{
    require_admin();
    if (!csrf_valid()) {
        json_out(array('ok' => false, 'error' => '보안 확인이 만료되었어요. 새로고침해 주세요.'), 400);
    }
    if (!has_upload('image')) {
        json_out(array('ok' => false, 'error' => '사진을 골라 주세요.'), 422);
    }
    try {
        json_out(array('ok' => true, 'path' => store_image($_FILES['image'], 'blog')));
    } catch (RuntimeException $e) {
        json_out(array('ok' => false, 'error' => $e->getMessage()), 422);
    }
}

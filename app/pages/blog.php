<?php
/**
 * 그린청소 블로그: 공개 화면(/blog, /blog/번호-글이름, /rss.xml)과 관리자(글 목록·쓰기·사진 올리기).
 */

function page_blog_list()
{
    indexnow_due();
    visit_track();
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
    indexnow_due();
    visit_track();
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
    // 조회수: 방문 한 번에 2~3씩 올립니다(관리자가 볼 때는 세지 않음). 화면에는 올린 뒤 숫자를 보여 줍니다.
    if (!$admin) {
        $add = random_int(2, 3);
        q('UPDATE blog_posts SET views = views + ? WHERE id = ?', array($add, (int) $post['id']));
        $post['views'] = (int) $post['views'] + $add;
    }
    render('cleaning/blog_post', array(
        'post' => $post,
        'related' => blog_latest(3, (int) $post['id']),
        'preview' => !blog_is_public($post),
    ), null);
}

/** RSS 2.0: 최근 글 30개(요약 + 본문 전체). 네이버 · 다음 · 구글에 RSS로 제출하는 주소예요. */
function blog_rss()
{
    indexnow_due();
    header('Content-Type: application/rss+xml; charset=utf-8');
    $name = gc('name');
    $base = base_url();
    $posts = blog_latest(30);
    $built = q_value('SELECT MAX(updated_at) FROM blog_posts WHERE ' . blog_public_sql(), array(now()));
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/"><channel>' . "\n"
        . '<title>' . e($name . ' 블로그') . '</title>'
        . '<link>' . e($base . '/blog') . '</link>'
        . '<atom:link href="' . e($base . '/rss.xml') . '" rel="self" type="application/rss+xml"/>'
        . '<description>' . e($name . ' – 충북 음성·진천·혁신도시 사무실·상가·공장·화장실 청소 이야기') . '</description>'
        . '<language>ko</language>'
        . ($built ? '<lastBuildDate>' . e(date('r', strtotime($built))) . '</lastBuildDate>' : '') . "\n";
    foreach ($posts as $p) {
        // 본문 속 사진 · 링크의 /로 시작하는 주소는 전체 주소로(다른 사이트에서 읽어도 보이게)
        $html = preg_replace('/(src|href)="\/(?!\/)/', '$1="' . $base . '/', blog_body_html($p));
        $image = blog_image($p);
        if ($image !== '') {
            $html = '<p><img src="' . e(strpos($image, '/') === 0 ? $base . $image : $image) . '" alt="' . e($p['title']) . '"></p>' . "\n" . $html;
        }
        echo '<item><title>' . e($p['title']) . '</title>'
            . '<link>' . e(blog_url($p, true)) . '</link>'
            . '<guid isPermaLink="true">' . e(blog_url($p, true)) . '</guid>'
            . '<pubDate>' . e(date('r', strtotime($p['published_at']))) . '</pubDate>'
            . '<description>' . e(blog_desc($p, 200)) . '</description>'
            . '<content:encoded><![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $html) . ']]></content:encoded></item>' . "\n";
    }
    echo '</channel></rss>';
    exit;
}

/* ───────── 관리자 ───────── */

function admin_blog_list()
{
    require_admin();
    $posts = q_all('SELECT * FROM blog_posts ORDER BY COALESCE(published_at, created_at) DESC, id DESC');
    render_admin('blog_list', array(
        'title' => '블로그', 'nav' => 'blog',
        'posts' => $posts,
        'drafts' => blog_drafts(),
        'usedTitles' => array_flip(array_column($posts, 'title')),
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
    // 준비된 초안으로 쓰기(/admin/blog/new?draft=번호): 제목 · 키워드 · 설명 · 본문을 채워 둡니다.
    $drafts = blog_drafts();
    $draftNo = !$post && !is_post() ? input_int('draft', 0) : 0;
    if ($draftNo >= 1 && isset($drafts[$draftNo - 1])) {
        list($form['title'], $form['keywords'], $form['summary'], $form['body']) = $drafts[$draftNo - 1];
    }
    // 에디터는 서식 있는 글(HTML)로 보여 줍니다. 예전 글(간단 표시)은 열 때 HTML로 바꿔 두고, 저장하면 HTML 글이 됩니다.
    if (!blog_is_html($form)) {
        $form['body'] = blog_render($form['body']);
        $form['format'] = 'html';
    }
    $here = $post ? '/admin/blog/' . (int) $post['id'] . '/edit' : '/admin/blog/new';
    if (is_post() && !$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = '올린 사진이 서버 한도(post_max_size ' . ini_get('post_max_size') . ')보다 커요.';
    } elseif (is_post()) {
        require_csrf($here);
        $status = input('status') === 'draft' ? 'draft' : 'published';
        $date = input('published_date');
        // 에디터는 본문을 base64로 감싸 보냅니다(카페24 웹 방화벽이 HTML 태그가 든 요청을 막지 않게).
        $format = input('format') === 'html' ? 'html' : 'md';
        $body = str_replace("\r\n", "\n", (string) input('body'));
        if ($format === 'html') {
            $b64 = base64_decode((string) input('body_b64'), true);
            $body = rich_clean_html($b64 !== false && $b64 !== '' ? $b64 : $body);
        }
        $form = array_merge($form, array(
            'title' => str_cut(trim(preg_replace('/\s+/u', ' ', input('title'))), 200, ''),
            'body' => $body,
            'format' => $format,
            'summary' => str_cut(trim(preg_replace('/\s+/u', ' ', input('summary'))), 300, ''),
            'seo_title' => str_cut(trim(input('seo_title')), 200, ''),
            'keywords' => str_cut(trim(input('keywords')), 300, ''),
            'slug' => blog_slugify(input('slug') !== '' ? input('slug') : input('title')),
            'status' => $status,
        ));
        if ($form['title'] === '') {
            $errors[] = '글 제목을 적어 주세요.';
        }
        if ($status === 'published' && str_len(blog_text($form)) < 20) {
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
                    $coverNote = image_report_text('대표 사진');
                }
            } catch (RuntimeException $e) {
                $errors[] = '대표 사진: ' . $e->getMessage();
            }
        }
        if (!$errors) {
            $wasPublic = $post && blog_is_public($post);
            $data = array_intersect_key($form, array_flip(array('title', 'slug', 'summary', 'body', 'format', 'cover', 'seo_title', 'keywords', 'status', 'published_at')));
            $data['updated_at'] = now();
            if ($post) {
                q_update('blog_posts', (int) $post['id'], $data);
                $newId = (int) $post['id'];
            } else {
                $data['created_at'] = now();
                $newId = q_insert('blog_posts', $data);
            }
            // 검색 사이트에 알리기: 공개 글이면 그 주소, 공개를 내렸으면 사라진 주소(목록 · 첫 화면도 함께)
            $saved = find_blog_post($newId);
            $isPublic = blog_is_public($saved);
            $paths = array();
            if ($isPublic) {
                $paths[] = blog_url($saved);
            }
            if ($wasPublic && (!$isPublic || blog_url($post) !== blog_url($saved))) {
                $paths[] = blog_url($post);
            }
            indexnow_schedule();
            $pinged = $paths ? indexnow_ping(array_merge($paths, array('/blog', '/')), $isPublic ? ($post ? '블로그 글 수정' : '블로그 글 공개') : '블로그 글 내림') : null;
            $coverNote = $coverNote ?? '';
            if ($isPublic) {
                flash('글을 저장하고 공개했어요.' . ($pinged ? indexnow_result_text($pinged) : '') . ' 검색 결과에는 보통 며칠 안에 반영돼요.' . $coverNote);
            } elseif ($status === 'published') {
                flash(date('Y.m.d', strtotime($saved['published_at'])) . '에 공개되도록 예약했어요. 공개되면 검색 사이트에 자동으로 알려요.' . $coverNote);
            } else {
                flash('임시저장했어요. 공개하려면 ‘공개’로 바꿔 저장하세요.' . $coverNote);
            }
            redirect('/admin/blog/' . $newId . '/edit');
        }
    }
    render_admin('blog_form', array('title' => $post ? '글 고치기' : '새 글 쓰기', 'nav' => 'blog', 'post' => $post, 'form' => $form, 'errors' => $errors, 'fromDraft' => $draftNo >= 1 && isset($drafts[$draftNo - 1])));
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
    indexnow_schedule();
    // 공개돼 있던 글이면 사라진 주소를 검색 사이트에 알려 검색 결과에서도 빨리 빠지게
    $pinged = blog_is_public($post) ? indexnow_ping(array(blog_url($post), '/blog', '/'), '블로그 글 삭제') : null;
    flash('‘' . $post['title'] . '’ 글을 지웠어요.' . ($pinged ? indexnow_result_text($pinged) : ''));
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
        json_out(array('ok' => true, 'path' => store_image($_FILES['image'], 'blog', null, true)));
    } catch (RuntimeException $e) {
        json_out(array('ok' => false, 'error' => $e->getMessage()), 422);
    }
}

/**
 * 에디터에 붙여 넣기: 복사한 글(HTML, base64로 감쌈)을 정리하고 사진은 우리 서버로 가져와 돌려줍니다.
 * 반환: {"ok":true,"html":"…","imported":가져온 사진 수,"failed":[못 가져온 사진 주소]}
 */
function admin_blog_paste()
{
    require_admin();
    if (!csrf_valid()) {
        json_out(array('ok' => false, 'error' => '보안 확인이 만료되었어요. 새로고침해 주세요.'), 400);
    }
    $raw = (string) ($_POST['html_b64'] ?? '');
    if (strlen($raw) > 8 * 1048576) {
        json_out(array('ok' => false, 'error' => '한 번에 붙여 넣기에는 너무 길어요. 나눠서 붙여 넣어 주세요.'), 413);
    }
    $html = base64_decode($raw, true);
    if ($html === false) {
        json_out(array('ok' => false, 'error' => '붙여 넣은 내용을 읽지 못했어요.'), 422);
    }
    list($html, $imported, $failed) = rich_import_images(rich_clean_html($html, true));
    json_out(array('ok' => true, 'html' => rich_clean_html($html), 'imported' => $imported, 'failed' => $failed));
}

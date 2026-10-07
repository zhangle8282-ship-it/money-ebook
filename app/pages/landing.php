<?php
/**
 * 그린청소 검색어 페이지: 공개 화면(/음성공장청소 처럼)과 관리자(목록 · 쓰기 · 지우기).
 */

function page_landing($slug)
{
    landing_seed();
    $page = find_landing_by_slug($slug);
    $admin = $page && $page['status'] !== 'published' ? current_admin() : null;
    if (!$page || ($page['status'] !== 'published' && !$admin)) {
        not_found();
    }
    indexnow_due();
    render('cleaning/landing', array(
        'page' => $page,
        'others' => array_values(array_filter(landing_public(), function ($p) use ($page) {
            return (int) $p['id'] !== (int) $page['id'];
        })),
        'posts' => blog_latest(3),
        'preview' => $page['status'] !== 'published',
    ), null);
}

/* ───────── 관리자 ───────── */

function admin_landing_list()
{
    require_admin();
    landing_seed();
    render_admin('landing_list', array(
        'title' => '검색어 페이지', 'nav' => 'pages',
        'pages' => q_all('SELECT * FROM landing_pages ORDER BY sort, id'),
    ));
}

function admin_landing_form($id = null)
{
    require_admin();
    $page = $id !== null ? find_landing($id) : null;
    if ($id !== null && !$page) {
        not_found();
    }
    $form = $page ?: array(
        'title' => '', 'slug' => '', 'summary' => '', 'body' => '', 'cover' => '', 'seo_title' => '', 'keywords' => '',
        'kind' => 'office', 'status' => 'published',
        'sort' => (int) q_value('SELECT COALESCE(MAX(sort), 0) FROM landing_pages') + 1,
    );
    $errors = array();
    $here = $page ? '/admin/pages/' . (int) $page['id'] . '/edit' : '/admin/pages/new';
    if (is_post() && !$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = '올린 사진이 서버 한도(post_max_size ' . ini_get('post_max_size') . ')보다 커요.';
    } elseif (is_post()) {
        require_csrf($here);
        $form = array_merge($form, array(
            'title' => str_cut(trim(preg_replace('/\s+/u', ' ', input('title'))), 200, ''),
            'body' => str_replace("\r\n", "\n", (string) input('body')),
            'summary' => str_cut(trim(preg_replace('/\s+/u', ' ', input('summary'))), 300, ''),
            'seo_title' => str_cut(trim(input('seo_title')), 200, ''),
            'keywords' => str_cut(trim(input('keywords')), 300, ''),
            'slug' => landing_slugify(input('slug') !== '' ? input('slug') : input('title')),
            'kind' => array_key_exists(input('kind'), CLEANING_KINDS) ? input('kind') : 'office',
            'status' => input('status') === 'hidden' ? 'hidden' : 'published',
            'sort' => max(0, min(999, input_int('sort', 0))),
        ));
        if ($form['title'] === '') {
            $errors[] = '페이지 제목(검색어)을 적어 주세요.';
        }
        if (!landing_slug_ok($form['slug'])) {
            $errors[] = '주소로 쓸 수 없는 이름이에요. 검색어를 한글로 적어 주세요(예: 음성공장청소).';
        } else {
            $same = find_landing_by_slug($form['slug']);
            if ($same && (!$page || (int) $same['id'] !== (int) $page['id'])) {
                $errors[] = '‘' . $form['slug'] . '’ 주소는 다른 페이지가 쓰고 있어요.';
            }
        }
        if ($form['status'] === 'published' && str_len(blog_plain($form['body'])) < 20) {
            $errors[] = '공개하려면 본문을 조금 더 써 주세요(20자 이상). 아직 쓰는 중이면 ‘숨김’으로 저장하세요.';
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
            $wasPublic = $page && $page['status'] === 'published';
            $data = array_intersect_key($form, array_flip(array('title', 'slug', 'summary', 'body', 'cover', 'seo_title', 'keywords', 'kind', 'sort', 'status')));
            $data['updated_at'] = now();
            if ($page) {
                q_update('landing_pages', (int) $page['id'], $data);
                $newId = (int) $page['id'];
            } else {
                $data['created_at'] = now();
                $newId = q_insert('landing_pages', $data);
            }
            // 검색 사이트에 알리기: 이 페이지(주소가 바뀌었으면 옛 주소도)와 첫 화면(목록이 바뀜)
            $saved = find_landing($newId);
            $paths = array();
            if ($saved['status'] === 'published') {
                $paths[] = landing_url($saved);
            }
            if ($wasPublic && ($saved['status'] !== 'published' || $page['slug'] !== $saved['slug'])) {
                $paths[] = landing_url($page);
            }
            $pinged = $paths ? indexnow_ping(array_merge($paths, array('/')), $page ? '검색어 페이지 수정' : '검색어 페이지 공개') : null;
            flash(($saved['status'] === 'published' ? '저장하고 공개했어요.' : '숨김으로 저장했어요. 홈페이지와 검색에는 나오지 않아요.') . ($pinged ? indexnow_result_text($pinged) : ''));
            redirect('/admin/pages/' . $newId . '/edit');
        }
    }
    render_admin('landing_form', array('title' => $page ? '검색어 페이지 고치기' : '새 검색어 페이지', 'nav' => 'pages', 'page' => $page, 'form' => $form, 'errors' => $errors));
}

function admin_landing_delete($id)
{
    require_admin();
    require_csrf('/admin/pages');
    $page = find_landing($id);
    if (!$page) {
        not_found();
    }
    q('DELETE FROM landing_pages WHERE id = ?', array((int) $page['id']));
    delete_public_file($page['cover']);
    $pinged = $page['status'] === 'published' ? indexnow_ping(array(landing_url($page), '/'), '검색어 페이지 삭제') : null;
    flash('‘' . $page['title'] . '’ 페이지를 지웠어요.' . ($pinged ? indexnow_result_text($pinged) : ''));
    redirect('/admin/pages');
}

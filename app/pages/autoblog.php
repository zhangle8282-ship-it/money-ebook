<?php
/**
 * 그린청소 관리자 › 블로그 › 자동 글쓰기 · 사진 창고, 그리고 Claude 예약 작업이 쓰는 글 받는 통로(/api/auto/…).
 */

/** 자동 글쓰기: 켜기 · 공개 시각 · 비밀 열쇠 · 다음 글 계획 · 최근 자동 글 */
function admin_autoblog()
{
    require_admin();
    $newToken = '';
    if (is_post()) {
        require_csrf('/admin/blog/auto');
        if (input('action') === 'token') {
            $newToken = auto_token_new();
            flash('새 비밀 열쇠를 만들었어요. 아래 열쇠를 지금 복사해 두세요. 다시 보여 주지 않아요.');
        } else {
            $ok = function ($t) {
                return preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', (string) $t) === 1;
            };
            $from = $ok(input('auto_from')) ? input('auto_from') : '08:30';
            $to = $ok(input('auto_to')) ? input('auto_to') : '20:30';
            if ((strtotime('2000-01-01 ' . $to) - strtotime('2000-01-01 ' . $from)) < 7200) {
                flash('시간대는 2시간 넘게 잡아 주세요(예: 08:30 ~ 20:30).', 'error');
                redirect('/admin/blog/auto');
            }
            save_settings(array('gc_auto_on' => input('auto_on') === '1' ? '1' : '', 'gc_auto_from' => $from, 'gc_auto_to' => $to, 'gc_auto_need_note' => input('need_note') === '1' ? '1' : ''));
            flash(input('auto_on') === '1' ? '자동 글쓰기를 켰어요. 하루 1개, ' . $from . ' ~ ' . $to . ' 사이에서 날마다 다른 시각에 공개돼요.' : '자동 글쓰기를 껐어요. 이미 예약된 글은 그대로 공개돼요.');
            redirect('/admin/blog/auto');
        }
    }
    render_admin('autoblog', array(
        'title' => '자동 글쓰기', 'nav' => 'blog',
        'plan' => auto_plan(),
        'newToken' => $newToken,
        'posts' => q_all('SELECT * FROM blog_posts WHERE auto = 1 ORDER BY published_at DESC, id DESC LIMIT 14'),
        'last' => json_decode((string) gc('auto_last'), true),
        'rejects' => array_slice((array) json_decode((string) gc('auto_rejects'), true), 0, 5),
    ));
}

/** 사진 창고: 여러 장 올리기(종류 고르기) · 종류 바꾸기 · 안 쓴 사진 지우기 */
function admin_stock()
{
    require_admin();
    if (is_post()) {
        require_csrf('/admin/blog/stock');
        $action = input('action');
        $kind = isset(AUTO_KINDS[input('kind')]) ? input('kind') : 'etc';
        if ($action === 'upload') {
            $files = $_FILES['photos'] ?? null;
            $ok = 0;
            $fail = array();
            if ($files && is_array($files['name'])) {
                foreach (array_keys($files['name']) as $i) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $one = array('name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]);
                    try {
                        $path = store_image($one, 'stock');
                        q_insert('photo_stock', array('path' => $path, 'kind' => $kind, 'memo' => str_cut(trim(input('memo')), 100, ''), 'created_at' => now()));
                        $ok++;
                    } catch (RuntimeException $e) {
                        $fail[] = $files['name'][$i] . ': ' . $e->getMessage();
                    }
                }
            }
            if ($ok) {
                flash('사진 ' . $ok . '장을 ‘' . (AUTO_KINDS[$kind][0] ?? '기타') . '’(으)로 사진 창고에 넣었어요. 올릴 때 화질은 지키고 용량만 줄였어요.' . ($fail ? ' 못 올린 사진: ' . implode(' / ', $fail) : ''));
            } else {
                flash($fail ? '사진을 올리지 못했어요: ' . implode(' / ', $fail) : '올릴 사진을 골라 주세요.', 'error');
            }
        } elseif ($action === 'kind') {
            q('UPDATE photo_stock SET kind = ? WHERE id = ?', array($kind, input_int('id')));
            flash('사진 종류를 바꿨어요.');
        } elseif ($action === 'delete') {
            $p = q_one('SELECT * FROM photo_stock WHERE id = ?', array(input_int('id')));
            if ($p && $p['used_post_id']) {
                flash('이미 글에 쓴 사진이라 지울 수 없어요(지우면 그 글의 대표 사진이 사라져요).', 'error');
            } elseif ($p) {
                delete_public_file($p['path']);
                q('DELETE FROM photo_stock WHERE id = ?', array((int) $p['id']));
                flash('사진을 지웠어요.');
            }
        }
        redirect('/admin/blog/stock' . (input('show') === 'used' ? '?show=used' : ''));
    }
    $show = input('show') === 'used' ? 'used' : 'unused';
    $photos = q_all('SELECT s.*, b.title AS post_title, b.id AS post_id FROM photo_stock s LEFT JOIN blog_posts b ON b.id = s.used_post_id WHERE '
        . ($show === 'used' ? 's.used_post_id IS NOT NULL' : 's.used_post_id IS NULL') . ' ORDER BY s.id ' . ($show === 'used' ? 'DESC' : 'ASC'));
    render_admin('stock', array(
        'title' => '사진 창고', 'nav' => 'blog', 'photos' => $photos, 'show' => $show,
        'counts' => stock_counts(), 'used' => (int) q_value('SELECT COUNT(*) FROM photo_stock WHERE used_post_id IS NOT NULL'),
    ));
}

/** 경험 노트: 적기 · 고치기 · 지우기(안 쓴 노트만), 그 현장 사진도 함께 올리기 */
function admin_notes()
{
    require_admin();
    $editId = input_int('edit');
    $edit = $editId ? q_one('SELECT * FROM experience_notes WHERE id = ? AND used_post_id IS NULL', array($editId)) : null;
    $errors = array();
    $form = $edit ?: array('kind' => 'office', 'region' => '', 'title' => '', 'body' => '', 'work_date' => '');
    if (is_post()) {
        require_csrf('/admin/blog/notes');
        if (input('action') === 'delete') {
            $n = q_one('SELECT * FROM experience_notes WHERE id = ?', array(input_int('id')));
            if ($n && !$n['used_post_id']) {
                foreach (q_all('SELECT * FROM photo_stock WHERE note_id = ? AND used_post_id IS NULL', array((int) $n['id'])) as $ph) {
                    delete_public_file($ph['path']);
                    q('DELETE FROM photo_stock WHERE id = ?', array((int) $ph['id']));
                }
                q('UPDATE photo_stock SET note_id = NULL WHERE note_id = ?', array((int) $n['id']));
                q('DELETE FROM experience_notes WHERE id = ?', array((int) $n['id']));
                flash('경험 노트를 지웠어요.');
            } else {
                flash('이미 글에 쓴 노트는 지울 수 없어요.', 'error');
            }
            redirect('/admin/blog/notes');
        }
        $form = array(
            'kind' => isset(AUTO_KINDS[input('kind')]) ? input('kind') : 'office',
            'region' => str_cut(trim(input('region')), 40, ''),
            'title' => str_cut(trim(preg_replace('/\s+/u', ' ', input('title'))), 120, ''),
            'body' => str_cut(trim(str_replace("\r\n", "\n", input('body'))), 5000, ''),
            'work_date' => valid_day(input('work_date')) ? input('work_date') : '',
        );
        if ($form['title'] === '') {
            $errors[] = '한 줄 제목을 적어 주세요(예: 금왕 ○○공장 휴게실 첫 정기청소).';
        }
        if (str_len($form['body']) < 80) {
            $errors[] = '경험 내용을 조금 더 자세히 적어 주세요(80자 이상). 아래 질문을 참고해 주세요.';
        }
        if (!$errors) {
            if ($edit) {
                q_update('experience_notes', (int) $edit['id'], $form + array('updated_at' => now()));
                $noteId = (int) $edit['id'];
                q('UPDATE photo_stock SET kind = ? WHERE note_id = ? AND used_post_id IS NULL', array($form['kind'], $noteId));
            } else {
                $noteId = (int) q_insert('experience_notes', $form + array('created_at' => now(), 'updated_at' => now()));
            }
            $ok = 0;
            $files = $_FILES['photos'] ?? null;
            if ($files && is_array($files['name'])) {
                foreach (array_keys($files['name']) as $i) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    try {
                        $path = store_image(array('name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]), 'stock');
                        q_insert('photo_stock', array('path' => $path, 'kind' => $form['kind'], 'memo' => str_cut($form['title'], 100, ''), 'note_id' => $noteId, 'created_at' => now()));
                        $ok++;
                    } catch (RuntimeException $e) {
                        $errors[] = $files['name'][$i] . ': ' . $e->getMessage();
                    }
                }
            }
            flash(($edit ? '경험 노트를 고쳤어요.' : '경험 노트를 저장했어요. 다음 자동 글부터 먼저 쓰여요.') . ($ok ? ' 현장 사진 ' . $ok . '장도 함께 넣었어요(이 노트로 쓴 글의 대표 사진이 돼요).' : '') . ($errors ? ' 못 올린 사진: ' . implode(' / ', $errors) : ''));
            redirect('/admin/blog/notes');
        }
    }
    $show = input('show') === 'used' ? 'used' : 'unused';
    render_admin('notes', array(
        'title' => '경험 노트', 'nav' => 'blog', 'form' => $form, 'edit' => $edit, 'errors' => $errors, 'show' => $show,
        'notes' => q_all('SELECT n.*, b.title AS post_title, (SELECT COUNT(*) FROM photo_stock s WHERE s.note_id = n.id) AS photos FROM experience_notes n LEFT JOIN blog_posts b ON b.id = n.used_post_id WHERE '
            . ($show === 'used' ? 'n.used_post_id IS NOT NULL ORDER BY n.used_at DESC' : 'n.used_post_id IS NULL ORDER BY n.id')),
        'left' => note_left(),
        'used' => (int) q_value('SELECT COUNT(*) FROM experience_notes WHERE used_post_id IS NOT NULL'),
    ));
}

/** 글 받는 통로: 열쇠 확인(틀리면 401), 꺼져 있으면 409 */
function api_auto_guard()
{
    header('Cache-Control: no-store');
    if (!auto_token_ok()) {
        json_out(array('ok' => false, 'error' => '열쇠가 맞지 않아요.'), 401);
    }
}

/** GET /api/auto/plan: 다음에 쓸 글(종류 · 지역 · 키워드 · 주제 · 공개 시각 · 최근 제목) */
function api_auto_plan()
{
    api_auto_guard();
    json_out(array('ok' => true, 'plan' => auto_plan()));
}

/** POST /api/auto/post: 글 받기(JSON: title, summary, keywords, body, kind) → 다음 빈 날 공개 예약 */
function api_auto_post()
{
    api_auto_guard();
    if (gc('auto_on') !== '1') {
        json_out(array('ok' => false, 'error' => '자동 글쓰기가 꺼져 있어요. 관리자 › 블로그 › 자동 글쓰기에서 켜 주세요.'), 409);
    }
    $in = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($in)) {
        $in = $_POST;
    }
    list($ok, $result) = auto_receive($in);
    json_out($ok ? array('ok' => true) + $result : array('ok' => false, 'errors' => $result), $ok ? 200 : 422);
}

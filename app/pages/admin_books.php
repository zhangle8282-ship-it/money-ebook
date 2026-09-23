<?php
/**
 * 관리자 · 전자책 등록/수정.
 * 미리보기: EPUB은 서버에서 앞부분 N쪽 분량을 뽑고, PDF는 관리자 브라우저(pdf.js)가
 * 앞 N쪽을 이미지로 그려 함께 올립니다. 원본 파일은 storage/ 에만 두고 공개하지 않습니다.
 */

function empty_book()
{
    return array(
        'id' => 0, 'title' => '', 'author' => '', 'category' => '', 'price' => '', 'pages' => null, 'description' => '',
        'cover_path' => '', 'file_path' => '', 'file_name' => '', 'file_size' => 0, 'file_format' => '',
        'preview_mode' => 'auto', 'preview_pages' => 10, 'preview_text' => '', 'preview_html' => '', 'preview_images' => '[]',
        'status' => 'draft', 'published_at' => null,
    );
}

function admin_book_form($id = null)
{
    require_admin();
    $book = $id ? q_one('SELECT * FROM books WHERE id = ?', array((int) $id)) : null;
    if ($id && !$book) {
        not_found();
    }
    $form = $book ?: empty_book();
    $publish = $book ? $book['status'] !== 'hidden' : true;
    $errors = array();

    if (is_post()) {
        if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $errors[] = '올린 파일이 서버 한도(post_max_size ' . ini_get('post_max_size') . ')보다 커요.';
        } elseif (!csrf_valid()) {
            $errors[] = '보안 확인이 만료되었어요. 다시 시도해 주세요.';
        } else {
            $draft = input('intent') === 'draft';
            $publish = input('publish') === '1';
            $form = array_merge($form, array(
                'title' => str_cut(input('title'), 200, ''),
                'author' => str_cut(input('author'), 120, ''),
                'category' => str_cut(input('category'), 60, ''),
                'price' => input('price') === '' ? '' : input_int('price'),
                'pages' => input('pages') === '' ? null : input_int('pages'),
                'description' => str_replace("\r\n", "\n", input('description')),
                'preview_mode' => input('preview_mode') === 'manual' ? 'manual' : 'auto',
                'preview_pages' => max(1, min(100, input_int('preview_pages', 10))),
                'preview_text' => str_replace("\r\n", "\n", input('preview_text')),
            ));
            $errors = validate_book($form, $draft);
            if (!$errors) {
                try {
                    $saved = save_book($book, $form, $draft, $publish);
                    flash($saved['message'], $saved['warning'] ? 'error' : 'ok');
                    redirect('/admin/books/' . $saved['id'] . '/edit');
                } catch (RuntimeException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }

    render_admin('book_form', array(
        'title' => $book ? '전자책 수정' : '새 전자책 등록',
        'nav' => 'books',
        'book' => $book,
        'form' => $form,
        'publish' => $publish,
        'errors' => $errors,
        'categories' => array_values(array_unique(array_filter(array_merge(categories(), array($form['category'])), 'strlen'))),
    ));
}

function validate_book($form, $draft)
{
    $errors = array();
    if ($form['title'] === '') {
        $errors[] = '제목을 입력해 주세요.';
    }
    if ($draft) {
        return $errors;
    }
    if ($form['author'] === '') {
        $errors[] = '저자를 입력해 주세요.';
    }
    if ($form['category'] === '') {
        $errors[] = '카테고리를 선택해 주세요.';
    }
    if ($form['price'] === '' || (int) $form['price'] < 100) {
        $errors[] = '판매가를 100원 이상으로 입력해 주세요.';
    }
    if ($form['file_path'] === '' && !has_upload('book_file')) {
        $errors[] = '전자책 파일(EPUB 또는 PDF)을 올려 주세요.';
    }
    return $errors;
}

/** 파일 저장 → DB 저장 → 미리보기 만들기. 반환: [id, message, warning] */
function save_book($book, $form, $draft, $publish)
{
    $old = $book ?: empty_book();
    $newCover = '';
    $newFile = null;

    if (has_upload('cover')) {
        $newCover = store_image($_FILES['cover'], 'covers');
    }
    if (has_upload('book_file')) {
        try {
            $newFile = store_book_file($_FILES['book_file']);
        } catch (RuntimeException $e) {
            delete_public_file($newCover);
            throw $e;
        }
    }

    $row = array(
        'title' => $form['title'],
        'author' => $form['author'],
        'category' => $form['category'],
        'price' => (int) $form['price'],
        'pages' => $form['pages'],
        'description' => $form['description'],
        'preview_mode' => $form['preview_mode'],
        'preview_pages' => (int) $form['preview_pages'],
        'preview_text' => $form['preview_text'],
        'status' => $draft ? 'draft' : ($publish ? 'on_sale' : 'hidden'),
        'updated_at' => now(),
    );
    if ($row['status'] === 'on_sale' && empty($old['published_at'])) {
        $row['published_at'] = now();
    }
    if ($newCover !== '' || input('remove_cover') === '1') {
        $row['cover_path'] = $newCover;
        delete_public_file($old['cover_path']);
    }
    if ($newFile) {
        list($row['file_path'], $row['file_name'], $row['file_size'], $row['file_format']) = $newFile;
        if (book_file_path($old) !== '') {
            @unlink(book_file_path($old));
        }
    }

    if ($book) {
        $id = (int) $book['id'];
        q_update('books', $id, $row);
    } else {
        $row['created_at'] = now();
        $id = q_insert('books', $row);
    }

    $current = q_one('SELECT * FROM books WHERE id = ?', array($id));
    $warning = build_preview($current, $old, $newFile !== null);
    $labels = array('draft' => '임시저장했어요.', 'hidden' => '저장했어요. 비공개라 스토어에는 보이지 않아요.', 'on_sale' => '저장했어요. 스토어에 판매 중으로 보여요.');
    return array(
        'id' => $id,
        'message' => $warning !== '' ? $warning : $labels[$row['status']],
        'warning' => $warning !== '',
    );
}

/** 미리보기 범위가 바뀌었거나 파일이 새로 올라오면 미리보기를 다시 만듭니다. 문제가 있으면 안내 문구를 돌려줍니다. */
function build_preview($book, $old, $fileChanged)
{
    $id = (int) $book['id'];
    $changed = $fileChanged
        || (int) $book['preview_pages'] !== (int) $old['preview_pages']
        || $book['preview_mode'] !== $old['preview_mode'];
    $update = array();
    $warning = '';

    if ($book['preview_mode'] === 'manual') {
        if ($book['preview_html'] !== '' || book_preview_images($book)) {
            delete_preview_images($id);
            $update = array('preview_html' => '', 'preview_images' => '[]');
        }
    } elseif ($book['file_format'] === 'EPUB') {
        if ($changed || trim((string) $book['preview_html']) === '') {
            $maxChars = (int) $book['preview_pages'] * (int) config('chars_per_page');
            $result = epub_extract(book_file_path($book), $maxChars);
            delete_preview_images($id);
            $update = array('preview_images' => '[]', 'preview_html' => $result ? $result['html'] : '');
            if (!$result || $result['html'] === '') {
                $warning = 'EPUB에서 미리보기 본문을 찾지 못했어요. ‘직접 입력’으로 미리보기를 넣어 주세요.';
            } elseif ($book['pages'] === null) {
                $update['pages'] = max(1, (int) ceil($result['total_chars'] / (int) config('chars_per_page')));
            }
        }
    } elseif ($book['file_format'] === 'PDF') {
        $images = uploaded_preview_images();
        if ($images) {
            delete_preview_images($id);
            $saved = array();
            foreach (array_slice($images, 0, 100) as $i => $file) {
                try {
                    $saved[] = store_image($file, 'previews/' . $id, ($i + 1) . '-' . bin2hex(random_bytes(3)));
                } catch (RuntimeException $e) {
                    $warning = '미리보기 이미지 일부를 저장하지 못했어요: ' . $e->getMessage();
                    break;
                }
            }
            $update = array('preview_html' => '', 'preview_images' => json_encode($saved));
        } elseif ($changed || !book_preview_images($book)) {
            if ($fileChanged) {
                delete_preview_images($id);
                $update = array('preview_html' => '', 'preview_images' => '[]');
            }
            $warning = 'PDF 미리보기 이미지를 만들지 못했어요. 수정 화면에서 다시 저장하거나 ‘직접 입력’을 써 주세요.';
        }
        $pdfPages = input_int('pdf_pages');
        if ($book['pages'] === null && $pdfPages > 0) {
            $update['pages'] = $pdfPages;
        }
    }
    if ($update) {
        q_update('books', $id, $update);
    }
    return $book['status'] === 'draft' && $warning !== '' ? '' : $warning;
}

/** preview_images[] 로 올라온 파일 목록을 하나씩 나눕니다. */
function uploaded_preview_images()
{
    $f = $_FILES['preview_images'] ?? null;
    if (!$f || !is_array($f['name'])) {
        return array();
    }
    $list = array();
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = array('name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]);
    }
    return $list;
}

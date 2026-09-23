<?php
/**
 * 기본 설정. 서버마다 다른 값은 같은 폴더에 config.local.php 를 만들어 덮어쓰세요.
 *
 *   <?php return ['db' => ['dsn' => 'mysql:host=localhost;dbname=아이디;charset=utf8mb4', 'user' => '아이디', 'pass' => '비밀번호']];
 */
return array(
    'db' => array(
        // 기본은 파일 하나로 동작하는 SQLite. 카페24 등에서 MySQL을 쓰려면 위 예시처럼 바꿉니다.
        'dsn' => 'sqlite:' . STORAGE_DIR . '/store.sqlite',
        'user' => null,
        'pass' => null,
    ),
    'debug' => false,
    // 전자책 파일 최대 크기(MB). PHP의 upload_max_filesize / post_max_size 도 이보다 커야 합니다.
    'max_book_mb' => 200,
    // EPUB 미리보기에서 1쪽으로 치는 글자 수(한글 단행본 기준 대략값)
    'chars_per_page' => 600,
);

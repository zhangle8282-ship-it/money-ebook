<?php
/**
 * 터미널 도구
 *   php app/cli.php demo          디자인 시안의 예시 전자책 8권을 판매 중으로 넣습니다(관리자에서 지울 수 있어요).
 *   php app/cli.php reset-admin   관리자 계정을 지웁니다. 다음에 /admin 에 들어가면 계정을 새로 만듭니다.
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
require __DIR__ . '/bootstrap.php';

$command = $argv[1] ?? '';

if ($command === 'demo') {
    $books = array(
        array('느리게 걷는 아침', '한서윤', '에세이', 12000, 240, '알람보다 먼저 눈을 뜬 아침, 목적지 없이 동네를 걷는 사람의 기록. 속도를 늦추자 비로소 보이기 시작한 골목의 이름들과 작은 풍경을 담은 산문집입니다.'),
        array('작은 가게의 숫자들', '박도현', '경제·경영', 15000, 280, ''),
        array('밤의 도서관', '윤채원', '소설', 11000, 320, ''),
        array('처음 만드는 웹 서비스', '이준호', 'IT', 18000, 360, ''),
        array('하루 한 페이지 습관', '정민아', '자기계발', 9900, 180, ''),
        array('바다가 보이는 부엌', '최유진', '에세이', 13000, 220, ''),
        array('겨울 기차', '김태오', '소설', 10500, 260, ''),
        array('조용한 리더십', '오세린', '자기계발', 14000, 240, ''),
    );
    $preview = "1장. 알람보다 먼저\n\n몇 해 전부터 알람이 울리기 전에 눈을 뜨는 날이 많아졌다. 처음에는 잠이 줄어든 탓이라 생각했지만, 곧 그 시간이 하루 중 유일하게 아무도 나를 찾지 않는 시간이라는 걸 알게 됐다.\n\n창밖은 아직 푸르스름하고, 골목 끝 빵집만 불을 켜 두었다. 나는 물 한 잔을 마시고 운동화 끈을 묶는다. 목적지는 정하지 않는다. 오늘 걸을 길은 발이 먼저 고른다.\n\n빨리 걷지 않기로 한 건 순전히 우연이었다. 무릎이 아파 천천히 걷던 어느 봄날, 평소라면 지나쳤을 것들이 하나씩 눈에 들어왔다. 담장 위에서 졸던 고양이, 세탁소 유리문에 붙은 손글씨 안내문, 매일 같은 자리에 물을 주는 할머니의 화분들.\n\n그날 이후로 나는 아침마다 속도를 조금씩 늦췄다. 늦춘 만큼 하루가 넓어졌다. 이 책은 그렇게 넓어진 아침들에 대한 기록이다.";
    $i = 0;
    foreach (array_reverse($books) as $b) {
        $t = date('Y-m-d H:i:s', time() - (count($books) - $i++) * 86400);
        q_insert('books', array(
            'title' => $b[0], 'author' => $b[1], 'category' => $b[2], 'price' => $b[3], 'pages' => $b[4],
            'description' => $b[5] !== '' ? $b[5] : '[예시 전자책] 책 소개를 입력하세요.',
            'preview_mode' => 'manual', 'preview_pages' => 10, 'preview_text' => $b[0] === '느리게 걷는 아침' ? $preview : '',
            'status' => 'on_sale', 'published_at' => $t, 'created_at' => $t, 'updated_at' => $t,
        ));
    }
    echo "예시 전자책 " . count($books) . "권을 넣었어요. 파일이 없는 예시라 실제 판매 전에 지우거나 파일을 올려 주세요.\n";
} elseif ($command === 'reset-admin') {
    q('DELETE FROM admins');
    echo "관리자 계정을 지웠어요. /admin 에 들어가면 새 계정을 만들 수 있어요.\n";
} else {
    echo "사용법: php app/cli.php demo | reset-admin\n";
}

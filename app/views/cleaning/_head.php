<?php
/**
 * 그린청소 공개 화면 <head>: 검색 노출(제목·설명·대표 주소·공유 정보·구조화 데이터)을 한곳에서 만듭니다.
 * 변수: $meta[title, desc, canonical, type(website|article), image, keywords(bool), ld(배열), published, modified]
 */
$m = $meta + array('type' => 'website', 'image' => base_url() . '/assets/green-og.jpg', 'keywords' => false, 'ld' => array(), 'published' => null, 'modified' => null);
$naver = verify_code(gc('naver_verify'));
$google = verify_code(gc('google_verify'));
$bing = verify_code(gc('bing_verify'));
?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($m['title']) ?></title>
<meta name="description" content="<?= e($m['desc']) ?>">
<?php if ($m['keywords'] && gc('seo_keywords') !== ''): ?><meta name="keywords" content="<?= e(gc('seo_keywords')) ?>">
<?php endif; ?>
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="<?= e($m['canonical']) ?>">
<?php if ($naver !== ''): ?><meta name="naver-site-verification" content="<?= e($naver) ?>">
<?php endif; ?>
<?php if ($google !== ''): ?><meta name="google-site-verification" content="<?= e($google) ?>">
<?php endif; ?>
<?php if ($bing !== ''): ?><meta name="msvalidate.01" content="<?= e($bing) ?>">
<?php endif; ?>
<meta property="og:type" content="<?= e($m['type']) ?>">
<meta property="og:locale" content="ko_KR">
<meta property="og:site_name" content="<?= e(gc('name')) ?>">
<meta property="og:title" content="<?= e($m['title']) ?>">
<meta property="og:description" content="<?= e($m['desc']) ?>">
<meta property="og:url" content="<?= e($m['canonical']) ?>">
<meta property="og:image" content="<?= e($m['image']) ?>">
<?php if ($m['published']): ?><meta property="article:published_time" content="<?= e(date('c', strtotime($m['published']))) ?>">
<?php endif; ?>
<?php if ($m['modified']): ?><meta property="article:modified_time" content="<?= e(date('c', strtotime($m['modified']))) ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#2F7D5C">
<?= icon_links() ?>
<?php if (blog_has_posts()): ?><link rel="alternate" type="application/rss+xml" title="<?= e(gc('name')) ?> 블로그" href="/rss.xml">
<?php endif; ?>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
<link rel="stylesheet" href="<?= e(cleaning_asset('green.css')) ?>">
<script>document.documentElement.classList.add('js');</script>
<?php foreach ($m['ld'] as $ld): ?><script type="application/ld+json"><?= str_replace('</', '<\/', json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></script>
<?php endforeach; ?>
<?= custom_code('head') ?>
</head>

<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . '/styles.css');
?><!DOCTYPE html>
<html lang="<?= LANGUAGE_ID ?>">
<head>
    <?php $APPLICATION->ShowHead(); ?>
    <title><?php $APPLICATION->ShowTitle(); ?></title>
</head>
<body>
<?php $APPLICATION->ShowPanel(); ?>
<header class="site-header">
    <div class="site-header__inner">
        <div class="site-header__logo">INT1</div>
        <nav class="site-header__nav">
            <a href="/" class="site-header__link">Главная</a>
            <a href="/int1/" class="site-header__link">Группы</a>
            <a href="/groups/" class="site-header__link">Группы (ЧПУ)</a>
        </nav>
    </div>
</header>
<main class="site-main">
    <div class="container">


<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

use Bitrix\Main\Page\Asset;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta class="js-meta-viewport" name="viewport" content="width=device-width, height=device-height, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
	<meta name="format-detection" content="telephone=no">
	
	<title><? $APPLICATION->ShowTitle(); ?></title>

	<?php
	$templatePath = SITE_TEMPLATE_PATH;
	Asset::getInstance()->addCss($templatePath . '/assets/template_styles.css');
	Asset::getInstance()->addJs($templatePath . '/assets/js/vendor/inputmask.min.js');
	Asset::getInstance()->addJs($templatePath . '/assets/js/vendor/swiper-bundle.min.js');
	Asset::getInstance()->addJs($templatePath . '/assets/js/build.js');
	?>

	<? $APPLICATION->ShowHead(); ?>

	<style>
		html, body {
			height: 100%;
			margin: 0;
			padding: 0;
		}
		body {
			display: flex;
			flex-direction: column;
		}
		.website-workarea {
			flex: 1 0 auto;
		}
		.footer {
			flex-shrink: 0;
		}
		.footer__nav {
			margin: 20px 0 !important;
		}
		.footer-nav-list {
			display: flex !important;
			gap: 20px !important;
			justify-content: center !important;
			margin: 0 !important;
			padding: 0 !important;
		}
		.footer-nav__item {
			color: #fff !important;
			text-decoration: none !important;
			font-size: 14px !important;
			text-transform: uppercase !important;
			transition: color 0.3s !important;
		}
		.footer-nav__item:hover,
		.footer-nav__item_active {
			color: #d4a574 !important;
		}

		.header {
			background: #000 !important;
			padding: 20px 0 !important;
			position: sticky !important;
			top: 0 !important;
			z-index: 1000 !important;
		}
		.container {
			max-width: 1200px;
			margin: 0 auto;
			padding: 0 15px;
		}
		.header-wrapper {
			display: flex;
			align-items: center;
			justify-content: space-between;
		}
		.header__logo {
			display: block !important;
		}
		.header__logo img {
			display: block !important;
			max-width: 250px !important;
			height: auto !important;
		}
		.header__burger {
			display: none;
		}
		.header-nav {
			display: flex;
			align-items: center;
			gap: 40px;
		}
		.nav-list {
			display: flex !important;
			gap: 30px !important;
			margin: 0 !important;
			padding: 0 !important;
			list-style: none !important;
		}
		.nav-list__item {
			color: #fff !important;
			text-decoration: none !important;
			font-size: 14px !important;
			font-weight: 400 !important;
			text-transform: uppercase !important;
			letter-spacing: 1px !important;
			padding: 10px 0 !important;
		}
		.nav-list__item:hover {
			color: #d4a574 !important;
		}
		.header__phone {
			display: block !important;
		}
		.header__phone a {
			color: #fff !important;
			text-decoration: none !important;
			font-size: 16px !important;
			font-weight: 500 !important;
			padding: 10px 0 !important;
		}
		.header__phone a:hover {
			color: #d4a574 !important;
		}
		
		.blog_article {
			padding: 60px 15px !important;
			max-width: 1000px !important;
			margin: 0 auto !important;
			color: #fff !important;
			background: #000 !important;
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		.blog_article img {
			width: 100% !important;
			height: auto !important;
			margin-bottom: 40px !important;
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		.blog_article h1 {
			font-size: 36px !important;
			color: #d4a574 !important;
			margin: 40px 0 30px !important;
			text-align: center !important;
			text-transform: uppercase !important;
			font-weight: 700 !important;
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		.blog_article p {
			font-size: 16px !important;
			line-height: 1.8 !important;
			margin-bottom: 20px !important;
			color: #fff !important;
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		.blog-detail__text {
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		
		.fancybox-content {
			background: #2a2a2a !important;
			border: 3px solid #888 !important;
			padding: 40px 50px !important;
			max-width: 600px !important;
			box-shadow: 0 0 30px rgba(0,0,0,0.8) !important;
		}
		
		.popup-feedback__input-label {
			color: #fff !important;
			font-size: 14px !important;
			margin-bottom: 8px !important;
		}
		
		.popup-feedback__input,
		.popup-feedback__textarea {
			background: #3a3a3a !important;
			border: 1px solid #4a4a4a !important;
			color: #fff !important;
			padding: 12px 15px !important;
		}
		
		.popup-feedback__textarea {
			min-height: 100px !important;
		}
		
		.button_modal-gold {
			background: #c5a262 !important;
			color: #000 !important;
			padding: 15px 50px !important;
			font-weight: 700 !important;
			text-transform: uppercase !important;
		}
	</style>
	
	<?php if (strpos($APPLICATION->GetCurPage(), 'statya-') !== false): ?>
	<style>
		body, html {
			background: #000 !important;
			color: #fff !important;
		}
		
		.website-workarea {
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		
		section {
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		
		.container {
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		
		.blog_article,
		.blog_article * {
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
		
		.blog_article img {
			display: block !important;
		}
		
		.blog_article h1,
		.blog_article h2,
		.blog_article h3,
		.blog_article p,
		.blog_article div {
			display: block !important;
			visibility: visible !important;
			opacity: 1 !important;
		}
	</style>
	<?php endif; ?>
</head>
<body>

<div id="panel"><? $APPLICATION->ShowPanel(); ?></div>

<!-- НАЧАЛО ШАБЛОНА -->
<header class="header">
	<div class="container">
		<div class="header-wrapper">
			<a href="/" class="header__logo">
				<img width="300" height="92" src="<?=SITE_TEMPLATE_PATH?>/assets/images/svg/logo-yanicode.svg" alt="yanicode">
			</a>
			<div class="header__burger header__burger_close">
				<span class="burger-line"></span>
				<span class="burger-line"></span>
				<span class="burger-line"></span>
			</div>
			<div class="header-nav">
				<nav class="nav-list">
					<? $APPLICATION->IncludeComponent(
						"bitrix:menu", 
						"top_menu", 
						[
							"ALLOW_MULTI_SELECT" => "N",
							"CHILD_MENU_TYPE" => "left",
							"DELAY" => "N",
							"MAX_LEVEL" => "1",
							"MENU_CACHE_GET_VARS" => [],
							"MENU_CACHE_TIME" => "3600",
							"MENU_CACHE_TYPE" => "A",
							"MENU_CACHE_USE_GROUPS" => "Y",
							"ROOT_MENU_TYPE" => "top",
							"USE_EXT" => "Y",
							"COMPONENT_TEMPLATE" => "top_menu"
						],
						false
					); ?>
				</nav>
				<div class="header__phone">
					<a href="tel:+79114510616">+79114510616</a>
				</div>
			</div>
		</div>
	</div>
</header>

<main class="website-workarea">


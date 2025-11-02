<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
$APPLICATION->SetTitle("Главная");

include __DIR__.'/home-content.html';


CModule::IncludeModule("iblock");
$resBlog = CIBlock::GetList(Array(), Array("CODE" => "blog", "TYPE" => "content"));
if ($arBlog = $resBlog->Fetch() && $arBlog['ID']) {
	$APPLICATION->IncludeComponent(
		"bitrix:news.list",
		"blog",
		Array(
			"IBLOCK_TYPE" => "content",
			"IBLOCK_ID" => $arBlog['ID'],
			"NEWS_COUNT" => "4",
			"SORT_BY1" => "ACTIVE_FROM",
			"SORT_ORDER1" => "DESC",
			"FILTER_NAME" => "",
			"FIELD_CODE" => array("PREVIEW_TEXT", "PREVIEW_PICTURE", "DATE_ACTIVE_FROM"),
			"PROPERTY_CODE" => array(),
			"DETAIL_URL" => "/blog/#ELEMENT_CODE#/",
			"AJAX_MODE" => "N",
			"CACHE_TYPE" => "A",
			"CACHE_TIME" => "3600",
			"CACHE_FILTER" => "Y",
			"CACHE_GROUPS" => "Y",
			"PREVIEW_TRUNCATE_LEN" => "200",
			"ACTIVE_DATE_FORMAT" => "d.m.Y",
			"SET_TITLE" => "N",
			"SET_BROWSER_TITLE" => "N",
			"SET_META_KEYWORDS" => "N",
			"SET_META_DESCRIPTION" => "N",
			"SET_LAST_MODIFIED" => "N",
			"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
			"ADD_SECTIONS_CHAIN" => "N",
			"HIDE_LINK_WHEN_NO_DETAIL" => "N",
			"PARENT_SECTION" => "",
			"PARENT_SECTION_CODE" => "",
			"INCLUDE_SUBSECTIONS" => "Y",
			"PAGER_TEMPLATE" => ".default",
			"DISPLAY_TOP_PAGER" => "N",
			"DISPLAY_BOTTOM_PAGER" => "N",
			"PAGER_TITLE" => "Новости",
			"PAGER_SHOW_ALWAYS" => "N",
			"PAGER_DESC_NUMBERING" => "N",
			"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
			"PAGER_SHOW_ALL" => "N",
		),
		false
	);
} else {
	?>
	<section class="container">
		<div class="blog-list">
			<a href="/statya-1.php" class="blog">
				<div class="blog__img">
					<img width="100%" height="100%" src="/www/local/assets/images/test_blog-img1.jpg" alt="">
				</div>
				<div class="blog__desc">
					<div class="blog__title">Что такое продакш-студия и для чего она нужна?</div>
					<div class="blog__date">07.12.2020</div>
					<div class="blog_article">Визуальный брендинг тематического ресторана «Базилик»: разработка логотипа, дизайн-концепт фирменного стиля и проработка его в носителях, таких как вывески, дизайн меню и карты вин, плейсметы, а также авторский надзор на стадии внедрения.</div>
				</div>
			</a>
			<a href="/statya-2.php" class="blog">
				<div class="blog__img">
					<img width="100%" height="100%" src="/www/local/assets/images/test_blog-img2.jpg" alt="">
				</div>
				<div class="blog__desc">
					<div class="blog__title">Личный бренд и что такое продакш- студия</div>
					<div class="blog__date">07.12.2020</div>
					<div class="blog_article">Визуальный брендинг тематического ресторана «Базилик»: разработка логотипа, дизайн-концепт фирменного стиля и проработка его в носителях, таких как вывески, дизайн меню и карты вин, плейсметы, а также авторский надзор на стадии внедрения.</div>
				</div>
			</a>
			<a href="/statya-3.php" class="blog">
				<div class="blog__img">
					<img width="100%" height="100%" src="/www/local/assets/images/test_blog-img3.jpg" alt="">
				</div>
				<div class="blog__desc">
					<div class="blog__title">Что такое продакш-студия и для чего она нужна?</div>
					<div class="blog__date">07.12.2020</div>
					<div class="blog_article">Визуальный брендинг тематического ресторана «Базилик»: разработка логотипа, дизайн-концепт фирменного стиля и проработка его в носителях, таких как вывески, дизайн меню и карты вин, плейсметы, а также авторский надзор на стадии внедрения.</div>
				</div>
			</a>
			<a href="/statya-4.php" class="blog">
				<div class="blog__img">
					<img width="100%" height="100%" src="/www/local/assets/images/test_blog-img4.jpg" alt="">
				</div>
				<div class="blog__desc">
					<div class="blog__title">Личный бренд и что такое продакш- студия</div>
					<div class="blog__date">07.12.2020</div>
					<div class="blog_article">Визуальный брендинг тематического ресторана «Базилик»: разработка логотипа, дизайн-концепт фирменного стиля и проработка его в носителях, таких как вывески, дизайн меню и карты вин, плейсметы, а также авторский надзор на стадии внедрения.</div>
				</div>
			</a>
		</div>
	</section>
	<?php
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
?>
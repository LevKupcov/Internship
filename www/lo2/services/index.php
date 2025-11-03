<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
$APPLICATION->SetTitle("Услуги");

include __DIR__.'/services-content.html';


$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"services",
	Array(
		"IBLOCK_TYPE" => "content",
		"IBLOCK_ID" => "services",
		"NEWS_COUNT" => "999",
		"SORT_BY1" => "PROPERTY_SERVICE_GROUP",
		"SORT_ORDER1" => "ASC",
		"SORT_BY2" => "SORT",
		"SORT_ORDER2" => "ASC",
		"FILTER_NAME" => "",
		"FIELD_CODE" => array("NAME", "PREVIEW_TEXT"),
		"PROPERTY_CODE" => array("SERVICE_GROUP"),
		"AJAX_MODE" => "N",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"CACHE_FILTER" => "Y",
		"CACHE_GROUPS" => "Y",
		"SET_TITLE" => "N",
		"SET_BROWSER_TITLE" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_LAST_MODIFIED" => "N",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"ADD_SECTIONS_CHAIN" => "N",
		"HIDE_LINK_WHEN_NO_DETAIL" => "Y",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"INCLUDE_SUBSECTIONS" => "Y",
		"PAGER_TEMPLATE" => ".default",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "N",
	),
	false
);
?>

<div class="container">
    <div class="services__img">
        <img width="100%" height="100%" src="/www/local/assets/images/img_services.jpg" alt="">
    </div>
    
    <div style="text-align: center; margin: 60px 0;">
        <div class="button button_gold button-open-calculate-project" data-popup="calculate-project-popup" style="padding: 20px 60px; font-size: 18px; cursor: pointer; display: inline-block;">
            РАССЧИТАТЬ ПРОЕКТ
        </div>
    </div>
</div>

<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
?>


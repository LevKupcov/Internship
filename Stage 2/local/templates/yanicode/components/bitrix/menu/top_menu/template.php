<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?php 
// Если меню пустое, выводим статичные ссылки
if (empty($arResult)): 
?>
	<a href="/" class="nav-list__item<?if($APPLICATION->GetCurPage() == '/'):?> nav-list__item_active<?endif?>">ГЛАВНАЯ</a>
	<a href="/services/" class="nav-list__item<?if(strpos($APPLICATION->GetCurPage(), '/services') !== false):?> nav-list__item_active<?endif?>">УСЛУГИ</a>
<?php else: ?>
	<?php foreach($arResult as $arItem): ?>
		<a href="<?=$arItem["LINK"]?>" class="nav-list__item<?if($arItem["SELECTED"]):?> nav-list__item_active<?endif?>"><?=$arItem["TEXT"]?></a>
	<?php endforeach ?>
<?php endif ?>


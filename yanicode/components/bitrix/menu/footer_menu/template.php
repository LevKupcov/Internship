<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?php if (!empty($arResult)): ?>
	<nav class="footer-nav-list">
		<?php foreach($arResult as $arItem): ?>
			<a href="<?=$arItem["LINK"]?>" class="footer-nav__item<?if($arItem["SELECTED"]):?> footer-nav__item_active<?endif?>"><?=$arItem["TEXT"]?></a>
		<?php endforeach ?>
	</nav>
<?php endif ?>


<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<section class="container">
    <div class="blog-list">
        <?foreach($arResult["ITEMS"] as $arItem):?>
            <?
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
            ?>
            <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="blog" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                <div class="blog__img">
                    <?if($arItem["PREVIEW_PICTURE"]):?>
                        <img width="100%" height="100%" src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$arItem["NAME"]?>">
                    <?else:?>
                        <img width="100%" height="100%" src="/www/local/assets/images/test_blog-img1.jpg" alt="<?=$arItem["NAME"]?>">
                    <?endif?>
                </div>
                <div class="blog__desc">
                    <div class="blog__title">
                        <?=$arItem["NAME"]?>
                    </div>
                    <div class="blog__date">
                        <?=$arItem["DISPLAY_ACTIVE_FROM"]?>
                    </div>
                    <div class="blog_article">
                        <?=$arItem["PREVIEW_TEXT"]?>
                    </div>
                </div>
            </a>
        <?endforeach;?>
    </div>
</section>


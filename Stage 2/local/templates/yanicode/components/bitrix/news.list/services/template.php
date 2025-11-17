<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
$this->setFrameMode(true);

// Группируем услуги по свойству SERVICE_GROUP
$arGrouped = array();
foreach($arResult["ITEMS"] as $arItem) {
    $groupName = $arItem["PROPERTIES"]["SERVICE_GROUP"]["VALUE"] ?: "Другое";
    $arGrouped[$groupName][] = $arItem;
}
?>

<div class="container">
    <h1 class="container-title">
        НАШИ УСЛУГИ
    </h1>
    <div class="services-cover">
        <?foreach($arGrouped as $groupName => $arItems):?>
            <div class="services">
                <h2 class="services__title">
                    <?=$groupName?>
                </h2>
                <div class="services-category">
                    <?foreach($arItems as $arItem):?>
                        <?
                        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"));
                        ?>
                        <div class="services__item js-popup-open" 
                             data-popup="services-popup" 
                             data-service-id="<?=$arItem['ID']?>"
                             data-service-name="<?=htmlspecialchars($arItem['NAME'])?>"
                             data-service-text="<?=htmlspecialchars($arItem['PREVIEW_TEXT'])?>"
                             id="<?=$this->GetEditAreaId($arItem['ID']);?>"
                             style="cursor: pointer;">
                            <?=$arItem["NAME"]?>
                        </div>
                    <?endforeach;?>
                </div>
            </div>
        <?endforeach;?>
    </div>
</div>


<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$group = $arResult['GROUP'];
?>
<div class="my-user-group-detail">
    <div class="my-user-group-detail__row">
        <span class="my-user-group-detail__label"><?= Loc::getMessage('MY_USER_GROUP_DETAIL_FIELD_ID'); ?>:</span>
        <span><?= (int)$group['ID']; ?></span>
    </div>
    <div class="my-user-group-detail__row">
        <span class="my-user-group-detail__label"><?= Loc::getMessage('MY_USER_GROUP_DETAIL_FIELD_NAME'); ?>:</span>
        <span><?= htmlspecialcharsbx($group['NAME']); ?></span>
    </div>
    <div class="my-user-group-detail__row">
        <span class="my-user-group-detail__label"><?= Loc::getMessage('MY_USER_GROUP_DETAIL_FIELD_DESCRIPTION'); ?>:</span>
        <span><?= htmlspecialcharsbx($group['DESCRIPTION']); ?></span>
    </div>
</div>


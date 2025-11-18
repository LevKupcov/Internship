<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$groupId = (int)($arResult['GROUP_ID'] ?? 0);

if ($groupId <= 0) {
    ShowError(Loc::getMessage('MY_USER_GROUPS_GROUP_NOT_FOUND'));
    return;
}
?>
<div class="my-user-groups my-user-groups--detail">
    <a href="<?= htmlspecialcharsbx($arResult['PATH_TO_LIST']); ?>" class="my-user-groups__back">
        <?= Loc::getMessage('MY_USER_GROUPS_BACK_TO_LIST'); ?>
    </a>
    <?php
    $APPLICATION->IncludeComponent(
        'my:user.group.detail',
        '',
        [
            'GROUP_ID' => $groupId,
        ],
        $component
    );
    ?>
</div>


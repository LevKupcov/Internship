<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$groups = $arResult['GROUPS'] ?? [];
?>
<div class="my-user-card" data-role="user-card">
    <?php if (!empty($groups)): ?>
        <table class="my-user-card__table">
            <thead>
            <tr>
                <th><?= Loc::getMessage('MY_USER_CARD_COLUMN_ID'); ?></th>
                <th><?= Loc::getMessage('MY_USER_CARD_COLUMN_NAME'); ?></th>
                <th><?= Loc::getMessage('MY_USER_CARD_COLUMN_DESCRIPTION'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $group): ?>
                <tr>
                    <td><?= (int)$group['ID']; ?></td>
                    <td><?= htmlspecialcharsbx($group['NAME']); ?></td>
                    <td><?= htmlspecialcharsbx($group['DESCRIPTION']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="my-user-card__empty">
            <?= Loc::getMessage('MY_USER_CARD_EMPTY_STATE'); ?>
        </div>
    <?php endif; ?>
</div>


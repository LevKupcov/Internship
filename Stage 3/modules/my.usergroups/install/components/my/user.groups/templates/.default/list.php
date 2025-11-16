<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$groups = $arResult['GROUPS'] ?? [];
?>
<div class="my-user-groups my-user-groups--list">
    <?php if (!empty($groups)): ?>
        <table class="my-user-card__table">
            <thead>
            <tr>
                <th><?= Loc::getMessage('MY_USER_GROUPS_COL_ID'); ?></th>
                <th><?= Loc::getMessage('MY_USER_GROUPS_COL_NAME'); ?></th>
                <th><?= Loc::getMessage('MY_USER_GROUPS_COL_DESCRIPTION'); ?></th>
                <th><?= Loc::getMessage('MY_USER_GROUPS_COL_LINK'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $group): ?>
                <tr>
                    <td><?= (int)$group['ID']; ?></td>
                    <td><?= htmlspecialcharsbx($group['NAME']); ?></td>
                    <td><?= htmlspecialcharsbx($group['DESCRIPTION']); ?></td>
                    <td>
                        <a href="<?= htmlspecialcharsbx($group['DETAIL_URL']); ?>">
                            <?= Loc::getMessage('MY_USER_GROUPS_LINK_DETAIL'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="my-user-card__empty">
            <?= Loc::getMessage('MY_USER_GROUPS_EMPTY'); ?>
        </div>
    <?php endif; ?>
</div>


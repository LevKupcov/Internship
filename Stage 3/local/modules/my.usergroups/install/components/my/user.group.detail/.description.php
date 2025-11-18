<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('MY_USER_GROUP_DETAIL_NAME'),
    'DESCRIPTION' => Loc::getMessage('MY_USER_GROUP_DETAIL_DESCRIPTION'),
    'PATH' => [
        'ID' => 'my_components',
        'NAME' => Loc::getMessage('MY_USER_GROUP_DETAIL_PATH_NAME'),
    ],
];


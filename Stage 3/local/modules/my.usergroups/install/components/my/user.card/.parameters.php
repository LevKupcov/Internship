<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'PARAMETERS' => [
        'PAGE_TITLE' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('MY_USER_CARD_PARAM_PAGE_TITLE'),
            'TYPE' => 'STRING',
            'DEFAULT' => Loc::getMessage('MY_USER_CARD_PARAM_PAGE_TITLE_DEFAULT'),
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 3600,
        ],
    ],
];


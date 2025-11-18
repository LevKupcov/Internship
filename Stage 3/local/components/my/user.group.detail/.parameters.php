<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'PARAMETERS' => [
        'GROUP_ID' => [
            'PARENT' => 'BASE',
            'NAME' => Loc::getMessage('MY_USER_GROUP_DETAIL_PARAM_GROUP_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 3600,
        ],
    ],
];


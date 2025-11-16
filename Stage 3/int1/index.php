<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Список групп пользователей');
?>

<h1 class="page-title"><?php $APPLICATION->ShowTitle(false); ?></h1>

<?php
$APPLICATION->IncludeComponent(
    'my:user.card',
    '.default',
    [
        'PAGE_TITLE' => 'Список групп пользователей',
        'CACHE_TIME' => 3600,
    ],
    false
);
?>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';


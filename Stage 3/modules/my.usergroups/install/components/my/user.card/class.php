<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\GroupTable;

Loc::loadMessages(__FILE__);

class MyUserCardComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;
        $params['PAGE_TITLE'] = trim((string)($params['PAGE_TITLE'] ?? ''));

        return $params;
    }

    protected function getGroups(): array
    {
        $groups = [];
        $groupIterator = GroupTable::getList([
            'select' => ['ID', 'NAME', 'DESCRIPTION'],
            'order' => ['NAME' => 'ASC'],
            'filter' => [],
        ]);

        while ($group = $groupIterator->fetch()) {
            $groups[] = [
                'ID' => (int)$group['ID'],
                'NAME' => $group['NAME'],
                'DESCRIPTION' => $group['DESCRIPTION'],
            ];
        }

        return $groups;
    }

    public function executeComponent()
    {
        global $APPLICATION;

        if (!Loader::includeModule('main')) {
            $this->abortResultCache();
            ShowError(Loc::getMessage('MY_USER_CARD_MAIN_MODULE_ERROR'));

            return;
        }

        if ($this->arParams['PAGE_TITLE'] !== '') {
            $APPLICATION->SetTitle($this->arParams['PAGE_TITLE']);
        }

        if ($this->startResultCache()) {
            try {
                $this->arResult['GROUPS'] = $this->getGroups();
                $this->includeComponentTemplate();
            } catch (\Throwable $exception) {
                $this->abortResultCache();
                ShowError($exception->getMessage());
            }
        }
    }
}


<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\GroupTable;

Loc::loadMessages(__FILE__);

class MyUserGroupDetailComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params['GROUP_ID'] = (int)($params['GROUP_ID'] ?? 0);
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        return $params;
    }

    protected function loadGroup(): ?array
    {
        $groupId = (int)$this->arParams['GROUP_ID'];

        if ($groupId <= 0) {
            return null;
        }

        $group = GroupTable::getList([
            'select' => ['ID', 'NAME', 'DESCRIPTION'],
            'filter' => ['=ID' => $groupId],
        ])->fetch();

        if (!$group) {
            return null;
        }

        return [
            'ID' => (int)$group['ID'],
            'NAME' => $group['NAME'],
            'DESCRIPTION' => $group['DESCRIPTION'],
        ];
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('main')) {
            ShowError(Loc::getMessage('MY_USER_GROUP_DETAIL_MAIN_MODULE_ERROR'));
            return;
        }

        if ($this->arParams['GROUP_ID'] <= 0) {
            ShowError(Loc::getMessage('MY_USER_GROUP_DETAIL_GROUP_NOT_FOUND'));
            return;
        }

        if ($this->startResultCache(false, [$this->arParams['GROUP_ID']])) {
            $group = $this->loadGroup();

            if (!$group) {
                $this->abortResultCache();
                ShowError(Loc::getMessage('MY_USER_GROUP_DETAIL_GROUP_NOT_FOUND'));
                return;
            }

            $this->arResult['GROUP'] = $group;
            $this->includeComponentTemplate();
        }
    }
}


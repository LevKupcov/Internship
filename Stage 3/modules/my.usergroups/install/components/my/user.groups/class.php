<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\GroupTable;

Loc::loadMessages(__FILE__);

class MyUserGroupsComponent extends CBitrixComponent
{
    protected array $urlTemplates = [];
    protected array $variables = [];
    protected string $componentPage = 'list';

    public function onPrepareComponentParams($params)
    {
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;
        $params['PAGE_TITLE'] = trim((string)($params['PAGE_TITLE'] ?? ''));
        $params['SEF_MODE'] = ($params['SEF_MODE'] ?? 'Y') !== 'N' ? 'Y' : 'N';
        $folder = (string)($params['SEF_FOLDER'] ?? '/');
        $params['SEF_FOLDER'] = rtrim($folder, '/') . '/';

        return $params;
    }

    protected function initSef(): void
    {
        $defaultTemplates = [
            'list' => '',
            'detail' => '#GROUP_ID#/',
        ];

        $componentEngine = new CComponentEngine($this);
        $this->urlTemplates = CComponentEngine::makeComponentUrlTemplates(
            $defaultTemplates,
            $this->arParams['SEF_URL_TEMPLATES'] ?? []
        );

        $this->componentPage = $componentEngine->parseComponentPath(
            $this->arParams['SEF_FOLDER'],
            $this->urlTemplates,
            $this->variables
        ) ?: 'list';

        CComponentEngine::initComponentVariables(
            $this->componentPage,
            ['GROUP_ID'],
            [],
            $this->variables
        );

        $this->arResult['FOLDER'] = $this->arParams['SEF_FOLDER'];
        $this->arResult['URL_TEMPLATES'] = $this->urlTemplates;
        $this->arResult['VARIABLES'] = $this->variables;
        $this->arResult['PATH_TO_LIST'] = $this->arParams['SEF_FOLDER'];
        $this->arResult['PATH_TO_DETAIL'] = $this->arParams['SEF_FOLDER'] . $this->urlTemplates['detail'];
    }

    protected function getGroups(): array
    {
        $groups = [];
        $iterator = GroupTable::getList([
            'select' => ['ID', 'NAME', 'DESCRIPTION'],
            'order' => ['NAME' => 'ASC'],
        ]);

        while ($group = $iterator->fetch()) {
            $groupId = (int)$group['ID'];
            $groups[] = [
                'ID' => $groupId,
                'NAME' => $group['NAME'],
                'DESCRIPTION' => $group['DESCRIPTION'],
                'DETAIL_URL' => $this->buildDetailUrl($groupId),
            ];
        }

        return $groups;
    }

    protected function buildDetailUrl(int $groupId): string
    {
        $relative = CComponentEngine::makePathFromTemplate(
            $this->urlTemplates['detail'],
            ['GROUP_ID' => $groupId]
        );

        return rtrim($this->arParams['SEF_FOLDER'], '/') . '/' . ltrim($relative, '/');
    }

    public function executeComponent()
    {
        global $APPLICATION;

        if (!Loader::includeModule('main')) {
            ShowError(Loc::getMessage('MY_USER_GROUPS_MAIN_MODULE_ERROR'));
            return;
        }

        $this->initSef();

        if ($this->componentPage === 'list' && $this->arParams['PAGE_TITLE'] !== '') {
            $APPLICATION->SetTitle($this->arParams['PAGE_TITLE']);
        }

        if ($this->componentPage === 'list') {
            if ($this->startResultCache(false, [$this->componentPage])) {
                try {
                    $this->arResult['GROUPS'] = $this->getGroups();
                    $this->includeComponentTemplate('list');
                } catch (\Throwable $exception) {
                    $this->abortResultCache();
                    ShowError($exception->getMessage());
                }
            }
        } elseif ($this->componentPage === 'detail') {
            $groupId = (int)($this->variables['GROUP_ID'] ?? 0);

            if ($groupId <= 0) {
                ShowError(Loc::getMessage('MY_USER_GROUPS_GROUP_NOT_FOUND'));
                return;
            }

            $this->arResult['GROUP_ID'] = $groupId;
            $this->includeComponentTemplate('detail');
        } else {
            $this->includeComponentTemplate('list');
        }
    }
}


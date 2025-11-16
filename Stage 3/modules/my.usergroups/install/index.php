<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class my_usergroups extends CModule
{
    public function __construct()
    {
        $this->MODULE_ID = 'my.usergroups';
        $this->MODULE_NAME = Loc::getMessage('MY_USERGROUPS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MY_USERGROUPS_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('MY_USERGROUPS_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('MY_USERGROUPS_PARTNER_URI');

        $versionFile = __DIR__ . '/version.php';

        if (is_file($versionFile)) {
            $arModuleVersion = [];
            include $versionFile;

            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
    }

    public function DoInstall()
    {
        $this->InstallFiles();
        RegisterModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        UnRegisterModule($this->MODULE_ID);
    }

    public function InstallFiles(): bool
    {
        $documentRoot = Application::getDocumentRoot();
        $source = __DIR__ . '/components/my';
        $destination = $documentRoot . '/local/components/my';

        if (!is_dir($destination)) {
            CheckDirPath($destination . '/');
        }

        CopyDirFiles($source, $destination, true, true);

        return true;
    }

    public function UnInstallFiles(): bool
    {
        $documentRoot = Application::getDocumentRoot();

        DeleteDirFilesEx('/local/components/my/user.card');
        DeleteDirFilesEx('/local/components/my/user.group.detail');
        DeleteDirFilesEx('/local/components/my/user.groups');

        return true;
    }
}


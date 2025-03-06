<?php

use Belov\Splitter\Listener\OrderSplitterHandler;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class belov_splitter extends CModule
{
    public $MODULE_ID = 'belov.splitter';

    public $MODULE_VERSION;

    public $MODULE_VERSION_DATE;

    public $MODULE_NAME;

    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = Loc::getMessage('BELOV_SPLITTER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BELOV_SPLITTER_MODULE_DESCRIPTION');
    }

    public function DoInstall(): void
    {
        $this->InstallEvents();
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function InstallEvents(): void
    {
        EventManager::getInstance()
            ->registerEventHandler(
                'sale',
                'OnSaleOrderBeforeSaved',
                $this->MODULE_ID,
                OrderSplitterHandler::class,
                'handle',
            );
    }

    public function DoUninstall(): void
    {
        $this->UnInstallEvents();
        ModuleManager::unregisterModule($this->MODULE_ID);
    }

    public function UninstallEvents(): void
    {
        EventManager::getInstance()
            ->unRegisterEventHandler(
                'sale',
                'OnSaleOrderBeforeSaved',
                $this->MODULE_ID,
                OrderSplitterHandler::class,
                'handle',
            );
    }
}
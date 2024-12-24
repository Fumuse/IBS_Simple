<?php

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;

class simple_events extends \CModule
{
    /**
     * @var EventManager
     */
    private $eventManager;

    public const MODULE_ID = "simple.events";

    private static $events = [
        [
            'main',
            'OnBeforeProlog',
            self::MODULE_ID,
            \Simple\Events\EventManager::class,
            "onProlog"
        ]
    ];

    public function __construct()
    {
        $this->MODULE_ID = 'simple.events';
        $this->MODULE_NAME = Loc::getMessage("SIMPLE_EVENTS_MODULE_NAME");
        $this->PARTNER_NAME = "simple";
        $this->PARTNER_URI = "simple";

        $this->setVersion();

        $this->eventManager = EventManager::getInstance();
    }

    protected function setVersion()
    {
        include __DIR__ . '/version.php';

        /** @var array $arModuleVersion */
        $this->MODULE_VERSION = $arModuleVersion['MODULE_VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['MODULE_VERSION_DATE'];
    }

    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallEvents();
    }

    public function InstallEvents()
    {
        foreach (self::$events as $event) {
            $this->eventManager->registerEventHandlerCompatible(...$event);
        }
    }

    function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UnInstallEvents();
    }

    public function UnInstallEvents()
    {
        foreach (self::$events as $event) {
            $this->eventManager->unRegisterEventHandler(...$event);
        }
    }
}
<?php
namespace Simple\Events\Events;

use \Bitrix\Main\EventManager;
use \Bitrix\Main\Event;
use \Bitrix\Main\Localization\Loc;

class SupplyZMKBeforeUpdate implements \Simple\Events\Interfaces\IEvent
{
    /**
     * @var EventManager $eventManager
     */
    protected EventManager $eventManager;

    protected array $events = [
        [
            'crm',
            'onBeforeCrmDynamicItemUpdate_' . \Emersion\Main\Config::SUPPLY_SPID,
            [
                __CLASS__,
                "onBeforeCrmDynamicItemUpdate"
            ]
        ],
    ];

    public function __construct()
    {
        $this->eventManager = EventManager::getInstance();
    }

    public function registerEvents()
    {
        foreach ($this->events as $event) {
            $this->eventManager->addEventHandlerCompatible(...$event);
        }
    }

    public static function onBeforeCrmDynamicItemUpdate(&$arFields)
    {
        $isCurrentSupplyIsProduct = (bool) $arFields["UF_IS_PRODUCT"];
        $clientZmk = $arFields['UF_CRM_33_ZMK'];

        if (!$isCurrentSupplyIsProduct)
        {
            if (empty($clientZmk))
            {
                $arFields['RESULT_MESSAGE'] = Loc::getMessage('ON_BEFORE_SUPPLY_ADD_CLIENT_EMPTY_ERROR');
                return false;
            }
        }
        else {
            if (!empty($clientZmk))
            {
                $arFields['RESULT_MESSAGE'] = Loc::getMessage('ON_BEFORE_SUPPLY_ADD_CLIENT_NOT_EMPTY_ERROR');
                return false;
            }
        }
        return true;
    }
}
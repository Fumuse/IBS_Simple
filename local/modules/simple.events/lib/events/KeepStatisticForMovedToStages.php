<?php
namespace Simple\Events\Events;

use \Simple\Events\Interfaces\IEvent;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Event;
use Statistics\Service\StatisticService;
use Triada\Helpers\ProjectCompanyHelper;

class KeepStatisticForMovedToStages implements IEvent
{
    /**
     * @var EventManager $eventManager
     */
    protected EventManager $eventManager;

    protected static bool $checkRecursion = false;

    protected array $events = [
        [
            'crm',
            'onCrmDynamicItemUpdate_' . \Emersion\Main\Config::SUPPLY_SPID,
            [
                __CLASS__,
                "afterSupplyUpdate"
            ]
        ],
        [
            'crm',
            'onCrmDynamicItemUpdate_' . ProjectCompanyHelper::PC_SPID,
            [
                __CLASS__,
                "afterProjectCompanyUpdate"
            ]
        ]
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

    public static function afterSupplyUpdate($item)
    {
        if (self::$checkRecursion) return;
        self::$checkRecursion = true;

        $stageCountable = StatisticService::getInstance()->getStageCountableSupplyByStageId(
            $item->get("STAGE_ID")
        );
        if (empty($stageCountable)) return;
        if (!empty($item->get($stageCountable::getStageDateField()))) return;

        $item->set($stageCountable::getStageDateField(), new \Bitrix\Main\Type\Datetime());
        if ($item->get("UF_IS_PRODUCT"))
        {
            $date = new \Datetime();
            $countersItem = [
                "DATE" => $date,
                "USER_ID" => $item->get("ASSIGNED_BY_ID"),
                "ENTITY_ID" => $item->get("ID"),
                "PRODUCT_ID" => $item->get("UF_CRM_33_VID"),
                "STAGE_ID" => $item->get("STAGE_ID"),
            ];

            $stageCountable->addToCounterTable($countersItem);
            $stageCountable->addToDetailCounterTable($countersItem);
        }

        $item->save();
    }

    public static function afterProjectCompanyUpdate($item)
    {
        if (self::$checkRecursion) return;
        self::$checkRecursion = true;

        $stageCountable = StatisticService::getInstance()->getStageCountableProjectCompanyByStageId(
            $item->get("STAGE_ID")
        );
        if (empty($stageCountable)) return;
        $dateField = $stageCountable::getStageDateField($item->get("STAGE_ID"));
        if (empty($dateField)) return;
        if (!empty($item->get($dateField))) return;

        $item->set($dateField, new \Bitrix\Main\Type\Datetime());

        $date = new \Datetime();
        $countersItem = [
            "DATE" => $date,
            "USER_ID" => $item->get("ASSIGNED_BY_ID"),
            "ENTITY_ID" => $item->get("ID"),
            "STAGE_ID" => $item->get("STAGE_ID"),
            "IS_MOVED" => true
        ];

        $stageCountable->addToCounterTable($countersItem);
        $stageCountable->addToDetailCounterTable($countersItem);

        $item->save();
    }
}
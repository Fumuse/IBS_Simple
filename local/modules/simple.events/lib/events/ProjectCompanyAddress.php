<?php
namespace Simple\Events\Events;

use \Simple\Events\Interfaces\IEvent;
use \Triada\Helpers\ProjectCompanyHelper;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Event;

class ProjectCompanyAddress implements IEvent
{
    /**
     * @var EventManager $eventManager
     */
    protected EventManager $eventManager;

    protected array $events = [];

    public function __construct()
    {
        $this->eventManager = EventManager::getInstance();
    }

    public function registerEvents()
    {
        $this->eventManager->addEventHandlerCompatible(
            'crm',
            'onCrmDynamicItemAdd_' . ProjectCompanyHelper::PC_SPID,
            [
                static::class,
                "afterProjectCompanyAdd"
            ]
        );
    }

    public static function afterProjectCompanyAdd($item)
    {
        if (empty($item->get("COMPANY_ID"))) return false;

        $addressFields = [
            $item->get("UF_ADDRESS_OBJECT"),
            $item->get("UF_COUNTRY_OBJECT"),
            $item->get("UF_REGION_OBJECT"),
            $item->get("UF_CITY_OBJECT")
        ];

        $filteredFields = array_filter($addressFields, function($value) {
            return !empty(trim($value));
        });
        if (!empty($filteredFields)) return false;

        $companyAddress = self::getCompanyFilledAddressFields($item->get("COMPANY_ID"));
        if (empty($companyAddress))
        {
            $companyAddress = self::getCompanyRequisiteAddress($item->get("COMPANY_ID"));
            if (empty($companyAddress)) return false;
        }

        $item->set("UF_ADDRESS_OBJECT", $companyAddress['UF_ADDRESS_OBJECT']);
        $item->set("UF_COUNTRY_OBJECT", $companyAddress['UF_COUNTRY_OBJECT']);
        $item->set("UF_REGION_OBJECT", $companyAddress['UF_REGION_OBJECT']);
        $item->set("UF_CITY_OBJECT", $companyAddress['UF_CITY_OBJECT']);
        $item->set("UF_DADATA_OBJECT_ADDRESS", "{}");

        $itemSave = $item->save();

        return $itemSave->isSuccess();
    }

    protected static function getCompanyFilledAddressFields(int $companyId) : array
    {
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Company);
        $companyDataClass = $factory->getDataClass();

        $company = $companyDataClass::getList([
            'select' => [
                "ID",
                "UF_ADDRESS_OBJECT",
                "UF_COUNTRY_OBJECT",
                "UF_REGION_OBJECT",
                "UF_CITY_OBJECT"
            ],
            'filter' => [
                '=ID' => $companyId
            ],
            'limit' => 1,
        ])->fetch() ?? [];

        if (empty($company)) return [];

        $addressFields = [
            "UF_ADDRESS_OBJECT" => $company["UF_ADDRESS_OBJECT"],
            "UF_COUNTRY_OBJECT" => $company["UF_COUNTRY_OBJECT"],
            "UF_REGION_OBJECT" => $company["UF_REGION_OBJECT"],
            "UF_CITY_OBJECT" => $company["UF_CITY_OBJECT"],
        ];

        $filteredFields = array_filter($addressFields, function($value) {
            return !empty(trim($value));
        });

        if (empty($filteredFields)) return [];
        return $addressFields;
    }

    protected static function getCompanyRequisiteAddress(int $companyId) : array
    {
        $addresses = \Bitrix\Crm\RequisiteAddress::getByEntities(\CCrmOwnerType::Company, [
            $companyId
        ]);

        $companyAddress = [];
        foreach ($addresses as $entityId => $requisite) {
            $requisiteAddresses = $requisite[key($requisite)];
            $addressFields = $requisiteAddresses[key($requisiteAddresses)];

            $companyAddress = [
                "UF_ADDRESS_OBJECT" => trim(implode(" ", [$addressFields["ADDRESS_1"], $addressFields["ADDRESS_2"]])),
                "UF_COUNTRY_OBJECT" => $addressFields["COUNTRY"],
                "UF_REGION_OBJECT" => $addressFields["REGION"],
                "UF_CITY_OBJECT" => $addressFields["CITY"],
            ];
        }

        return $companyAddress;
    }
}
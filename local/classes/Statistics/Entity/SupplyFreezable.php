<?php

namespace Statistics\Entity;

use Statistics\Interfaces\IFreezable;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Localization\Loc;

class SupplyFreezable implements IFreezable
{
    public function getFactory()
    {
        if (empty($this->factory))
            $this->factory = \Bitrix\Crm\Service\Container::getInstance()
                ->getFactory(\Emersion\Main\Config::SUPPLY_SPID);

        return $this->factory;
    }

    public function getCreateDateField() : string
    {
        return "CREATED_TIME";
    }

    public function getLastActivityDateField() : string
    {
        return "UF_CRM_1664978294348";
    }

    public function getFreezeField() : string
    {
        return "UF_SIGN_OF_FREENING";
    }

    public function getFrozenStage() : string
    {
        return "DT172_33:UC_16SLXP";
    }

    public function getFreezeFilterOptions() : array
    {
        $dateDeadlineFilter = strtotime("-60 days", time());
        $filterStages = [
            "DT172_33:FAIL",
            "DT172_33:SUCCESS",
            $this->getFrozenStage(),
        ];

        $filter = [
            [
                'LOGIC' => 'OR',
                [
                    "<=" . $this->getLastActivityDateField() => DateTime::createFromTimestamp($dateDeadlineFilter),
                ],
                [
                    '=' . $this->getLastActivityDateField() => null,
                    "<=" . $this->getCreateDateField() => DateTime::createFromTimestamp($dateDeadlineFilter),
                ],
            ],
            "=" . $this->getFreezeField() => false,
            '!=STAGE_ID' => $filterStages,
            '=UF_IS_PRODUCT' => true,
        ];

        return [
            'select' => [
                'ID', 'ASSIGNED_BY_ID', 'TITLE'
            ],
            'filter' => $filter,
        ];
    }

    public function getFrozenFilterOptions() : array
    {
        $dateDeadlineFilter = strtotime("-120 days", time());
        $filterStages = [
            "DT172_33:FAIL",
            "DT172_33:SUCCESS",
            $this->getFrozenStage(),
        ];

        $filter = [
            [
                'LOGIC' => 'OR',
                [
                    "<=" . $this->getLastActivityDateField() => DateTime::createFromTimestamp($dateDeadlineFilter),
                ],
                [
                    '=' . $this->getLastActivityDateField() => null,
                    "<=" . $this->getCreateDateField() => DateTime::createFromTimestamp($dateDeadlineFilter),
                ],
            ],
            "=" . $this->getFreezeField() => true,
            '!=STAGE_ID' => $filterStages,
            '=UF_IS_PRODUCT' => true,
        ];

        return [
            'select' => [
                'ID', 'ASSIGNED_BY_ID', 'TITLE'
            ],
            'filter' => $filter,
        ];
    }

    public function updateEntity(array $entity, string $stage): bool
    {
        if ($stage == "freeze")
            return $this->updateFreezeEntity($entity);
        if ($stage == "frozen")
            return $this->updateFrozeEntity($entity);

        return false;
    }

    protected function updateFreezeEntity(array $entity) : bool
    {
        $updateResult = $this->getFactory()->getDataClass()::update($entity['ID'], [
            $this->getFreezeField() => true,
        ]);

        return $updateResult->isSuccess();
    }

    protected function updateFrozeEntity(array $entity) : bool
    {
        $updateResult = $this->getFactory()->getDataClass()::update($entity['ID'], [
            "STAGE_ID" => $this->getFrozenStage(),
        ]);

        return $updateResult->isSuccess();
    }

    public function sendNotification(array $entity, string $stage) : bool
    {
        if ($stage == "freeze")
            return $this->sendFreezeNotification($entity);
        if ($stage == "frozen")
            return $this->sendFrozenNotification($entity);

        return false;
    }

    protected function sendFreezeNotification(array $entity): bool
    {
        $messagePlaceReplace = [
            '#ENTITY_DATA#' => "<b>{$entity['TITLE']}</b>",
            '#ENTITY_LINK#' => "/crm/type/172/details/{$entity['ID']}/"
        ];
        $arMessageFields = [
            "TO_USER_ID" => $entity['ASSIGNED_BY_ID'],
            "FROM_USER_ID" => 0,
            "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
            "NOTIFY_MODULE" => 'crm',
            "NOTIFY_TAG" => 'freeze_supply',
            "NOTIFY_MESSAGE" => Loc::getMessage("NOTIFICATION_FREEZE_ENTITY", $messagePlaceReplace),
            "NOTIFY_MESSAGE_OUT" => Loc::getMessage("NOTIFICATION_FREEZE_ENTITY", $messagePlaceReplace),
            "NOTIFY_EMAIL_TEMPLATE" => null,
        ];

        return \CIMNotify::Add($arMessageFields);
    }

    protected function sendFrozenNotification(array $entity): bool
    {
        $messagePlaceReplace = [
            '#ENTITY_DATA#' => "<b>{$entity['TITLE']}</b>",
            '#ENTITY_LINK#' => "/crm/type/172/details/{$entity['ID']}/"
        ];
        $arMessageFields = [
            "TO_USER_ID" => $entity['ASSIGNED_BY_ID'],
            "FROM_USER_ID" => 0,
            "NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
            "NOTIFY_MODULE" => 'crm',
            "NOTIFY_TAG" => 'freeze_supply',
            "NOTIFY_MESSAGE" => Loc::getMessage("NOTIFICATION_SEND_TO_FROZEN_ENTITY", $messagePlaceReplace),
            "NOTIFY_MESSAGE_OUT" => Loc::getMessage("NOTIFICATION_SEND_TO_FROZEN_ENTITY", $messagePlaceReplace),
            "NOTIFY_EMAIL_TEMPLATE" => null,
        ];
        return \CIMNotify::Add($arMessageFields);
    }
}
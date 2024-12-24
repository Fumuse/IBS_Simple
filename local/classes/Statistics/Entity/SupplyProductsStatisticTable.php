<?php

namespace Statistics\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Localization\Loc;

class SupplyProductsStatisticTable extends DataManager
{
    use \Simple\Traits\EntityUpdateOrCreate;

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'trd_supply_products_statistic_journal';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_ID_FIELD')
                ]
            ),

            new IntegerField(
                'USER_ID',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_USER_ID_FIELD'),
                    'index' => true
                ]
            ),

            new StringField(
                'STAGE_ID',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_STAGE_ID_FIELD')
                ]
            ),

            new IntegerField(
                'YEAR',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_YEAR_FIELD')
                ]
            ),

            new IntegerField(
                'MONTH',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_MONTH_FIELD')
                ]
            ),

            new IntegerField(
                'PRODUCT_ID',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_PRODUCT_ID_FIELD'),
                    'index' => true
                ]
            ),

            new IntegerField(
                'COUNT',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_COUNT_FIELD')
                ]
            ),

            new IntegerField(
                'COUNT_VOLUMES',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_COUNT_VOLUMES_FIELD')
                ]
            ),

            new IntegerField(
                'COUNT_ENGINEERS',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_COUNT_ENGINEERS_FIELD')
                ]
            ),

            new IntegerField(
                'COUNT_INACTIVE',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_COUNT_INACTIVE_FIELD')
                ]
            ),

            new IntegerField(
                'COUNT_FROZEN',
                [
                    'title' => Loc::getMessage('SP_STATISTIC_JOURNAL_ENTITY_COUNT_FROZEN_FIELD')
                ]
            ),
        ];
    }
}
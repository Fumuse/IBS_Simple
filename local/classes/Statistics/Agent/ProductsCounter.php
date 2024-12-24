<?php

namespace Statistics\Agent;

use Statistics\Interfaces\ICalculatedCountable;
use Statistics\Service\StatisticService;

class ProductsCounter
{
    public const GENERATOR_PAGE_COUNT = 50;

    protected static bool $getLastMonthStatistic = false;
    protected static \DateTime $agentDate;
    protected static ICalculatedCountable $productEntity;
    protected static \Statistics\Service\StatisticService $service;

    public static function run(string $countableEntity, bool $lastMonth = false) : ?string
    {
        self::includeModules();
        self::prepareOptions($countableEntity, $lastMonth);

        if (!empty(self::$productEntity))
            self::doCount();

        return "\\" . __METHOD__ . "('{$countableEntity}', {$lastMonth});";
    }

    protected static function includeModules()
    {
        \Bitrix\Main\Loader::includeModule("crm");

        self::$service = StatisticService::getInstance();
    }

    protected static function prepareOptions(string $countableEntity, bool $lastMonth = false)
    {
        self::$getLastMonthStatistic = $lastMonth;

        self::$agentDate = new \DateTime();
        if (!self::$getLastMonthStatistic)
            self::$agentDate->setTimestamp(strtotime("today 00:00:00"));
        else
            self::$agentDate->setTimestamp(strtotime("last day of last month 23:59:59"));

        self::$productEntity = self::$service->getProductEntity($countableEntity);
    }

    protected static function doCount()
    {
        foreach (self::entitiesGenerator() as $entities)
        {
            foreach ($entities as $entity)
            {
                $entity['DATE'] = self::$agentDate;
                self::$productEntity->calculateValues($entity);
                self::$productEntity->addToDetailCounterTable($entity);
            }
        }

        self::$productEntity->saveCalculatedValues(self::$agentDate);
    }

    protected static function entitiesGenerator(): \Generator
    {
        $dataClass = self::$productEntity->getFactory()->getDataClass();

        $start = 0;
        do {
            $getListOptions = self::$productEntity->getGetListOptionsForCountableEntities(
                ["agentDate" => self::$agentDate]
            );
            $getListOptions['offset'] = $start;
            $getListOptions['limit'] = self::GENERATOR_PAGE_COUNT;

            $getEntitiesList = $dataClass::getList($getListOptions);
            $entities = $getEntitiesList->fetchAll();
            if (!empty($entities))
            {
                yield $entities;
                $start += self::GENERATOR_PAGE_COUNT;
            }
        }
        while(!empty($entities));
    }
}
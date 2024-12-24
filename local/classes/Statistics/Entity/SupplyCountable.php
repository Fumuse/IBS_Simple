<?php

namespace Statistics\Entity;

use Statistics\Interfaces\ICalculatedCountable;

class SupplyCountable implements ICalculatedCountable
{
    use \Statistics\Traits\YearsAndMonths;
    
    public const FROZEN_STAGE = "DT172_33:UC_16SLXP";

    protected $factory = null;
    protected array $calculatedValues = [];

    protected \Simple\Helpers\BXUFDictionaries $ufDictionaryService;
    protected $connection;
    protected $sqlHelper;

    protected $stages = [];

    public function __construct()
    {
        $this->ufDictionaryService = \Simple\Helpers\BXUFDictionaries::getInstance();
        $this->connection = \Bitrix\Main\Application::getConnection();
        $this->sqlHelper = $this->connection->getSqlHelper();
    }

    public function getFactory()
    {
        if (empty($this->factory))
            $this->factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\Emersion\Main\Config::SUPPLY_SPID);

        return $this->factory;
    }

    public function getStages()
    {
        if (!empty($this->stages)) return $this->stages;
        $this->stages = $this->getFactory()->getStages();
        return $this->stages;
    }

    /**
     * @return SupplyProductsStatisticTable::class
     */
    public function getCounterTable(): string
    {
        return SupplyProductsStatisticTable::class;
    }

    /**
     * @return SupplyProductsHistoryStatisticTable::class
     */
    public function getDetailCounterTable(): string
    {
        return SupplyProductsHistoryStatisticTable::class;
    }

    public function getGetListOptionsForCountableEntities(array $options = []): array
    {
        return [
            "select" => [
                "ID", "ASSIGNED_BY_ID", "STAGE_ID",
                "UF_CRM_33_VID", "UF_CRM_33_OBJEM",
                "UF_PROJECT_ENGINEER", "UF_SIGN_OF_FREENING"
            ],
            "filter" => [
                '=UF_IS_PRODUCT' => true,
            ]
        ];
    }

    public function getCountExpressionField() : string
    {
        return "COUNT_FIELD";
    }

    public function getGetListOptionsForCountEntitiesWithFilter(array $filterData): array
    {
        $countField = "COUNT";
        $select = [
            $this->getCountExpressionField(),
        ];

        $filter = [
            "!STAGE_ID" => [
                "DT172_33:FAIL",
                "DT172_33:SUCCESS",
                self::FROZEN_STAGE,
            ]
        ];

        $this->prepareProductGetListFilter($filterData, $filter, "");
        $this->prepareYearAndMonthGetListFilter($filterData, $filter, "");

        if (!empty($filterData['USER_ID'])) {
            $filter["=USER_ID"] = $filterData['USER_ID'];
        }

        if (isset($filterData['ENGINEER'])) {
            $countField = "COUNT_ENGINEERS";
        }

        if (isset($filterData['VOLUMES'])) {
            $countField = "COUNT_VOLUMES";
        }

        if (isset($filterData['INACTIVE'])) {
            $countField = "COUNT_INACTIVE";
        }

        if (isset($filterData['FROZEN'])) {
            $countField = "COUNT_FROZEN";
            $filter["!STAGE_ID"] = array_filter($filter["!STAGE_ID"], function ($stage) {
                return $stage != self::FROZEN_STAGE; //ищем в том числе по замороженным
            });
        }

        $runtimes = [
            new \Bitrix\Main\ORM\Fields\ExpressionField($this->getCountExpressionField(), 'SUM(%s)', [$countField]),
        ];

        return [
            'runtime' => $runtimes,
            'filter' => $filter,
            'select' => $select,
        ];
    }

    public function getGetListOptionsForDetailEntitiesWithFilter(array $filterData): array
    {
        $runtimes = $this->getDetailCounterGetListRuntimes();

        $select = [
            'ID', 'TITLE',
            'YEAR' => 'DETAIL_COUNTER.YEAR', 'MONTH' => 'DETAIL_COUNTER.MONTH',
            'ASSIGNED_BY_ID', 'ASSIGNED_BY_FORMATTED_NAME',
        ];
        $filter = [
            "!DETAIL_COUNTER.STAGE_ID" => [
                "DT172_33:FAIL",
                "DT172_33:SUCCESS",
                self::FROZEN_STAGE,
            ]
        ];

        $this->prepareProductGetListFilter($filterData, $filter, "DETAIL_COUNTER.");
        $this->prepareYearAndMonthGetListFilter($filterData, $filter, "DETAIL_COUNTER.");

        if (isset($filterData['ENGINEER'])) {
            $filter['>UF_PROJECT_ENGINEER'] = 0;

            $this->getDetailCounterEngineerGetListRuntime($runtimes);

            $select['ENGINEER_ID'] = 'UF_PROJECT_ENGINEER';
            $select[] = 'ENGINEER_FORMATTED_NAME';
        }
        if (isset($filterData['VOLUMES'])) {
            $filter['>UF_CRM_33_OBJEM'] = 0;
            $select[] = 'UF_CRM_33_OBJEM';
        }
        if (isset($filterData['INACTIVE'])) {
            $filter['=UF_SIGN_OF_FREENING'] = true;
        }
        if (isset($filterData['FROZEN'])) {
            $filter['=UF_SIGN_OF_FREENING'] = true;
            unset($filter["!DETAIL_COUNTER.STAGE_ID"]);
            $filter["=DETAIL_COUNTER.STAGE_ID"] = [
                self::FROZEN_STAGE
            ];
        }
        if (!empty($filterData['USER_ID'])) {
            $filter["=DETAIL_COUNTER.USER_ID"] = $filterData['USER_ID'];
        }

        return [
            'runtime' => $runtimes,
            'filter' => $filter,
            'select' => $select,
        ];
    }

    protected function getDetailCounterGetListRuntimes() : array
    {
        return [
            new \Bitrix\Main\Entity\ReferenceField(
                'DETAIL_COUNTER',
                $this->getDetailCounterTable(),
                [
                    '=this.ID' => 'ref.ENTITY_ID',
                ],
                ['join_type' => 'INNER',]
            ),
            new \Bitrix\Main\Entity\ReferenceField(
                'ASSIGNED',
                \Bitrix\Main\UserTable::class,
                [
                    '=this.ASSIGNED_BY_ID' => 'ref.ID',
                ],
                ['join_type' => 'INNER',]
            ),
            new \Bitrix\Main\Entity\ExpressionField(
                'ASSIGNED_BY_FORMATTED_NAME',
                $this->sqlHelper->getConcatFunction(
                    "%s", "' '", "%s",
                ),
                ['ASSIGNED.LAST_NAME', 'ASSIGNED.NAME']
            ),
        ];
    }
    protected function getDetailCounterEngineerGetListRuntime(array &$runtimes) : void
    {
        $runtimes[] = new \Bitrix\Main\Entity\ReferenceField(
            'ENGINEER',
            \Bitrix\Main\UserTable::class,
            [
                '=this.UF_PROJECT_ENGINEER' => 'ref.ID',
            ],
            [
                'join_type' => 'INNER',
            ]
        );
        $runtimes[] = new \Bitrix\Main\Entity\ExpressionField(
            'ENGINEER_FORMATTED_NAME',
            $this->sqlHelper->getConcatFunction(
                "%s", "' '", "%s",
            ),
            ['ENGINEER.LAST_NAME', 'ENGINEER.NAME']
        );
    }
    protected function prepareProductGetListFilter(array &$filterData, array &$filter, string $runtimePrefix)
    {
        if (!empty($filterData['PRODUCT'])) {
            $ufField = $this->ufDictionaryService->getUserFieldEnumValueByXmlID(
                "UF_CRM_33_VID",
                $filterData['PRODUCT']
            );
            if (!empty($ufField)) {
                $filter[$runtimePrefix . 'PRODUCT_ID'] = $ufField['ID'];
            }
        }
    }
    protected function prepareYearAndMonthGetListFilter(array &$filterData, array &$filter, string $runtimePrefix)
    {
        if (!empty($filterData['MONTH'])) {
            $filter[$runtimePrefix . 'MONTH'] = $filterData['MONTH'];
        }
        if (!empty($filterData['YEAR'])) {
            $filter[$runtimePrefix . 'YEAR'] = $filterData['YEAR'];
        } else {
            $filter[$runtimePrefix . 'YEAR'] = date("Y");
            if (empty($filterData['MONTH'])) {
                $filter[$runtimePrefix . 'YEAR'] = date("n");
            }
        }
    }

    public function getListOfCalculatedValues(): array
    {
        return $this->calculatedValues;
    }

    protected function generateUserAllStagesValues(array &$item)
    {
        foreach ($this->getStages() as $stage)
        {
            if (!isset($this->calculatedValues[$item['UF_CRM_33_VID']][$stage->getStatusId()][$item['ASSIGNED_BY_ID']]))
            {
                $this->calculatedValues[$item['UF_CRM_33_VID']][$stage->getStatusId()][$item['ASSIGNED_BY_ID']] = [
                    'count' => 0,
                    'volumes' => 0,
                    'engineers' => 0,
                    'inactive' => 0,
                    'frozen' => 0,
                ];
            }
        }
    }

    public function calculateValues(array $item)
    {
        $this->generateUserAllStagesValues($item);

        $this->calculatedValues[$item['UF_CRM_33_VID']][$item['STAGE_ID']][$item['ASSIGNED_BY_ID']]['count']++;
        $this->calculatedValues[$item['UF_CRM_33_VID']][$item['STAGE_ID']][$item['ASSIGNED_BY_ID']]['volumes'] += $item['UF_CRM_33_OBJEM'];

        if (!empty($item['UF_PROJECT_ENGINEER'])) {
            $this->calculatedValues[$item['UF_CRM_33_VID']][$item['STAGE_ID']][$item['ASSIGNED_BY_ID']]['engineers']++;
        }

        if ((bool) $item['UF_SIGN_OF_FREENING']) {
            $this->calculatedValues[$item['UF_CRM_33_VID']][$item['STAGE_ID']][$item['ASSIGNED_BY_ID']]['inactive']++;
            
            if ($item['STAGE_ID'] == self::FROZEN_STAGE)
            {
                $this->calculatedValues[$item['UF_CRM_33_VID']][$item['STAGE_ID']][$item['ASSIGNED_BY_ID']]['frozen']++;
            }
        }
    }

    public function saveCalculatedValues(\DateTime $date)
    {
        foreach ($this->calculatedValues as $productId => $stages) {
            foreach ($stages as $stageId => $users) {
                foreach ($users as $userId => $counters) {
                    $this->addToCounterTable([
                        "USER_ID" => $userId,
                        "YEAR" => $date->format("Y"),
                        "MONTH" => $date->format("n"),
                        "STAGE_ID" => $stageId,
                        "PRODUCT_ID" => $productId,
                        "COUNT" => $counters['count'],
                        "COUNT_VOLUMES" => $counters['volumes'],
                        "COUNT_ENGINEERS" => $counters['engineers'],
                        "COUNT_INACTIVE" => $counters['inactive'],
                        "COUNT_FROZEN" => $counters['frozen'],
                    ]);
                }
            }
        }
    }

    public function addToCounterTable(array $item)
    {
        $this->getCounterTable()::updateOrCreate([
            "USER_ID" => $item['USER_ID'],
            "YEAR" => $item['YEAR'],
            "MONTH" => $item['MONTH'],
            "STAGE_ID" => $item['STAGE_ID'],
            "PRODUCT_ID" => $item['PRODUCT_ID'],
        ], [
            "COUNT" => $item['COUNT'],
            "COUNT_VOLUMES" => $item['COUNT_VOLUMES'],
            "COUNT_ENGINEERS" => $item['COUNT_ENGINEERS'],
            "COUNT_INACTIVE" => $item['COUNT_INACTIVE'],
            "COUNT_FROZEN" => $item['COUNT_FROZEN'],
        ]);
    }

    public function addToDetailCounterTable(array $item)
    {
        /**
         * @var \DateTime $date
         */
        $date = $item['DATE'];

        $this->getDetailCounterTable()::updateOrCreate([
            "USER_ID" => $item["ASSIGNED_BY_ID"],
            "YEAR" => $date->format("Y"),
            "MONTH" => $date->format("n"),
            "ENTITY_ID" => $item["ID"],
            "PRODUCT_ID" => $item["UF_CRM_33_VID"],
        ], [
            "STAGE_ID" => $item["STAGE_ID"],
            "HAS_ENGINEER" => !empty($item['UF_PROJECT_ENGINEER']) ? '1' : '0',
            "IS_INACTIVE" => (bool) $item['UF_SIGN_OF_FREENING'] ? '1' : '0',
            "IS_FROZEN" => ((bool) $item['UF_SIGN_OF_FREENING'] && $item['STAGE_ID'] == self::FROZEN_STAGE) ? '1' : '0',
        ]);
    }

    public function prepareDetailGridData(array &$item)
    {
        $item['ASSIGNED_BY'] = htmlspecialcharsbx($item['ASSIGNED_BY_FORMATTED_NAME']);
        if (isset($item['TITLE'])) {
            $detailUrl = "/crm/type/172/details/{$item['ID']}/";
            $item['TITLE'] = "<a href='{$detailUrl}'>{$item['TITLE']}</a>";
        }

        if (isset($item['ENGINEER_ID'])) {
            $item['ENGINEER'] = htmlspecialcharsbx($item['ENGINEER_FORMATTED_NAME']);
        }

        if (isset($item['UF_CRM_33_OBJEM'])) {
            $item['VOLUMES'] = $item['UF_CRM_33_OBJEM'];
        }

        if (isset($item['MONTH'])) {
            $item['MONTH'] = $this->getMonths()[$item['MONTH']];
        }
    }
}
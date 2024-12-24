<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Base;
use Simple\Entity\ProjectCompanyStatisticTable;

class CreateProjectCompanyStatisticTableAndAgents20240819140943 extends Version
{
    use \Simple\Traits\MigrationEntityCreateIndex;

    protected $description = "Создание таблицы статистики проектных компаний и агента сбора статистики";

    protected $moduleVersion = "4.1.3";

    public function up()
    {
        $helper = $this->getHelperManager();

        //Создание таблицы сбора статистики
        $connection = Application::getConnection();
        $table = Base::getInstance(ProjectCompanyStatisticTable::class);
        if (!$connection->isTableExists($table->getDBTableName())) {
            $table->createDBTable();
            $this->createIndex(ProjectCompanyStatisticTable::class);
        }

        //Создание ежедневного агента
        $date = new \DateTime();
        $date->setTimestamp(strtotime("tomorrow 23:45:01"));
        $helper->Agent()->saveAgent(array(
            'MODULE_ID' => 'main',
            'USER_ID' => NULL,
            'SORT' => '100',
            'NAME' => '\Simple\Agents\StatisticsProjectsCompaniesAgent::RunAgent(1);',
            'ACTIVE' => 'Y',
            'NEXT_EXEC' => $date->format("d.m.Y H:i:s"),
            'AGENT_INTERVAL' => '86400', //Раз в сутки - в секундах
            'IS_PERIOD' => 'N',
            'RETRY_COUNT' => '0',
        ));

        //Создание агента раз в месяц, для сбора статистики за прошлый месяц
        $date->setTimestamp(strtotime("first day of next month 00:01:00"));
        $helper->Agent()->saveAgent(array(
            'MODULE_ID' => 'main',
            'USER_ID' => NULL,
            'SORT' => '100',
            'NAME' => '\Simple\Agents\StatisticsProjectsCompaniesAgent::RunAgent(0);',
            'ACTIVE' => 'Y',
            'NEXT_EXEC' => $date->format("d.m.Y H:i:s"),
            'AGENT_INTERVAL' => 60 * 60 * 24 * 31, //Раз в месяц
            'IS_PERIOD' => 'N',
            'RETRY_COUNT' => '0',
        ));
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $helper->Sql()->query("DROP TABLE " . ProjectCompanyStatisticTable::getTableName());

        $helper->Agent()->deleteAgentIfExists(
            'main',
            '\Simple\Agents\StatisticsProjectsCompaniesAgent::RunAgent(1);'
        );
        $helper->Agent()->deleteAgentIfExists(
            'main',
            '\Simple\Agents\StatisticsProjectsCompaniesAgent::RunAgent(0);'
        );
    }
}

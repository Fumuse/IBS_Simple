<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\Base;
use Simple\Entity\ProjectCompanyHistoryElementsTable;

class CreateProjectCompanyStatisticHistoryElementsTable20240820164358 extends Version
{
    use \Simple\Traits\MigrationEntityCreateIndex;

    protected $description = "Создание таблицы исторических элементов проектных компаний";

    protected $moduleVersion = "4.1.3";

    public function up()
    {
        //Создание таблицы сбора статистики
        $connection = Application::getConnection();
        $table = Base::getInstance(ProjectCompanyHistoryElementsTable::class);
        if (!$connection->isTableExists($table->getDBTableName())) {
            $table->createDBTable();
            $this->createIndex(ProjectCompanyHistoryElementsTable::class);
        }
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $helper->Sql()->query("DROP TABLE " . ProjectCompanyHistoryElementsTable::getTableName());
    }
}

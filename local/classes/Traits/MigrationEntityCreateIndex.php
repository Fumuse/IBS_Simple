<?php

namespace Simple\Traits;

trait MigrationEntityCreateIndex
{
    public function createIndex(string $className)
    {
        $connection = \Bitrix\Main\Application::getInstance()->getConnection();

        $tableName = $className::getTableName();
        $fields = $className::getMap();
        foreach ($fields as $field) {
            $index = $field->getParameter("index") ?? false;
            if ($index)
            {
                $indexName = strtolower('idx_' . $tableName . '_' . $field->getName());
                $connection->createIndex($tableName, $indexName, $field->getName());
            }
        }
    }
}
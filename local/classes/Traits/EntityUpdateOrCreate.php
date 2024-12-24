<?php
namespace Triada\Traits;

trait EntityUpdateOrCreate
{
    public static function updateOrCreate(array $filter, array $data, string $primaryField = "ID")
    {
        $element = self::getList([
            "filter" => $filter,
            "select" => [$primaryField],
            "limit" => 1
        ])->fetch();

        if (!empty($element))
        {
            $result = self::update($element[$primaryField], $data);
        }
        else
        {
            $data = array_merge($filter, $data);
            $result = self::add($data);
        }

        return $result;
    }
}
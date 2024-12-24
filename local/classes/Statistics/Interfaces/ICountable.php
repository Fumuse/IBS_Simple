<?php

namespace Statistics\Interfaces;

interface ICountable
{
    public function getFactory();
    public function getCounterTable() : string;
    public function getDetailCounterTable() : string;
    public function getCountExpressionField() : string;
    public function getGetListOptionsForCountEntitiesWithFilter(array $filterData) : array;
    public function getGetListOptionsForDetailEntitiesWithFilter(array $filterData) : array;
    public function addToCounterTable(array $item);
    public function addToDetailCounterTable(array $item);
    public function prepareDetailGridData(array &$item);
}
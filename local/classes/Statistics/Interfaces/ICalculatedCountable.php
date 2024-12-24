<?php

namespace Statistics\Interfaces;

interface ICalculatedCountable extends ICountable
{
    public function getGetListOptionsForCountableEntities(array $options) : array;
    public function getListOfCalculatedValues() : array;
    public function calculateValues(array $item);
    public function saveCalculatedValues(\DateTime $date);
}
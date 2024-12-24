<?php

namespace Statistics\Interfaces;

interface IFreezable
{
    public function getFactory();
    public function getCreateDateField() : string;
    public function getLastActivityDateField() : string;
    public function getFreezeField() : string;
    public function getFrozenStage() : string;
    public function getFreezeFilterOptions() : array;
    public function getFrozenFilterOptions() : array;
    public function updateEntity(array $entity, string $stage) : bool;
    public function sendNotification(array $entity, string $stage);
}
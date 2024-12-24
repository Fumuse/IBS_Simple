<?php

namespace Simple\Events;

use DirectoryIterator;
use Simple\Events\Interfaces\IEvent;
use ReflectionClass;

class EventManager
{
    public static function onProlog()
    {
        static::registerEvents(__DIR__ . '/events');
    }

    protected static function registerEvents(string $directory)
    {
        $iterator = new DirectoryIterator($directory);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                include_once $directory . '/' . $fileInfo->getFilename();

                $className = $fileInfo->getBasename("." . $fileInfo->getExtension());
                $classNameWithNamespace = __NAMESPACE__ . '\\Events\\' . $className;
                if (class_exists($classNameWithNamespace))
                {
                    try {
                        $reflectionClass = new ReflectionClass($classNameWithNamespace);
                        if ($reflectionClass->implementsInterface(IEvent::class))
                        {
                            $classInstance = new $classNameWithNamespace;
                            $classInstance->registerEvents();
                        }
                    }
                    catch (\Exception $exception) {}
                }
            }
        }
    }
}
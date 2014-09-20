<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Defines the application config
 */
namespace RDev\Models\Applications\Configs;
use RDev\Models\Configs;

class ApplicationConfig extends Configs\Config
{
    /**
     * {@inheritdoc}
     */
    public function fromArray(array $configArray)
    {
        if(!$this->isValid($configArray))
        {
            throw new \RuntimeException("Invalid config");
        }

        if(!isset($configArray["environment"]))
        {
            $configArray["environment"] = [];
        }

        $this->configArray = $configArray;
    }

    /**
     * {@inheritdoc}
     */
    protected function isValid(array $configArray)
    {
        return parent::isValid($configArray);
    }
} 
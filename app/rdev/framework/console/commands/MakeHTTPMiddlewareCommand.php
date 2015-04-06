<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Makes an HTTP middleware class
 */
namespace RDev\Framework\Console\Commands;

class MakeCommandHTTPMiddlewareCommand extends MakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function define()
    {
        parent::define();

        $this->setName("make:httpmiddleware")
            ->setDescription("Creates an HTTP middleware class");
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . "\\HTTP\\Middleware";
    }

    /**
     * {@inheritdoc}
     */
    protected function getFileTemplatePath()
    {
        return __DIR__ . "/templates/HTTPMiddleware.template";
    }
}
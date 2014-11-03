<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Defines the interface for view builders to implement
 */
namespace RDev\Views;

interface IBuilder
{
    /**
     * Builds a template or a part of a template
     * Useful for centralizing creation of common components in templates
     *
     * @param Template $template The template to build
     * @return Template The built template
     */
    public function build(Template $template);
}
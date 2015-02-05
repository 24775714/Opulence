<?php
/**
 * Copyright (C) 2015 David Young
 * 
 * Defines the console commands container
 */
namespace RDev\Console\Commands;
use RDev\Console\Requests\Parsers;
use RDev\Console\Responses;

class Commands
{
    /** @var ICommand[] The list of commands */
    private $commands = [];
    /** @var Compilers\ICompiler The command compiler */
    private $commandCompiler = null;
    /** @var Parsers\ArrayList The request parser */
    private $requestParser = null;

    /**
     * @param Compilers\ICompiler $compiler The command compiler
     */
    public function __construct(Compilers\ICompiler $compiler)
    {
        $this->commandCompiler = $compiler;
        $this->requestParser = new Parsers\ArrayList();
    }

    /**
     * Adds a command
     *
     * @param ICommand $command The command to add
     * @throws \InvalidArgumentException Thrown if a command with the input name already exists
     */
    public function add(ICommand $command)
    {
        if($this->has($command->getName()))
        {
            throw new \InvalidArgumentException("A command with name \"{$command->getName()}\" already exists");
        }

        $command->setCommands($this);
        $this->commands[$command->getName()] = $command;
    }

    /**
     * Calls a command and writes its output to the input response
     *
     * @param string $commandName The name of the command to run
     * @param array $arguments The list of arguments
     * @param array $options The list of options
     * @param Responses\IResponse $response The response to write output to
     * @return int The status code of the command
     * @throws \InvalidArgumentException Thrown if no command exists with the input name
     */
    public function call($commandName, array $arguments, array $options, Responses\IResponse $response)
    {
        $request = $this->requestParser->parse(["name" => $commandName, "arguments" => $arguments, "options" => $options]);
        $compiledCommand = $this->commandCompiler->compile($this->get($commandName), $request);

        return $compiledCommand->execute($response);
    }

    /**
     * Gets the command with the input name
     *
     * @param string $name The name of the command to get
     * @return ICommand The command
     * @throws \InvalidArgumentException Thrown if no command with the input name exists
     */
    public function get($name)
    {
        if(!$this->has($name))
        {
            throw new \InvalidArgumentException("No command with name \"$name\" exists");
        }

        return $this->commands[$name];
    }

    /**
     * Gets all the commands
     *
     * @return ICommand[] The list of commands
     */
    public function getAll()
    {
        return array_values($this->commands);
    }

    /**
     * Checks if the input name has been added
     *
     * @param string $name The name of the command to look for
     * @return bool True if the command has been added, otherwise false
     */
    public function has($name)
    {
        return isset($this->commands[$name]);
    }
}
<?php


namespace Pff\DatabaseConfig\Contracts\Console;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Style\SymfonyStyle;

interface Application
{
    /**
     * Get the output from the last command.
     *
     * @return Input|ArgvInput
     */
    public function input();

    /**
     * Get the output from the last command.
     *
     * @return Output|SymfonyStyle
     */
    public function output();
}

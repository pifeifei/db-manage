<?php


namespace Pff\DatabaseConfig\Console;


use Pff\DatabaseConfig\Contracts\Console\Application as ApplicationContract;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Application
 * @package Pff\DatabaseConfig\Console
 */
class Application extends SymfonyApplication implements ApplicationContract
{
    /**
     * The output from the previous command.
     *
     * @var \Symfony\Component\Console\Output\BufferedOutput
     */
    protected $lastOutput;
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * Create a new Artisan console application.
     *
     * @param  string  $version
     * @return void
     */
    public function __construct($version)
    {
        parent::__construct('Database config', $version);

        $this->setAutoExit(false);
        $this->setCatchExceptions(false);

    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->lastOutput = $output;
        $this->input = $input;
        return parent::run($input, $output);
    }

    /**
     * @inheritDoc
     */
    public function input()
    {
        return $this->input;
    }

    /**
     * @inheritDoc
     */
    public function output()
    {
        return $this->lastOutput;
    }
    //
//    /**
//     * Run an Artisan console command by name.
//     *
//     * @param  string  $command
//     * @param  array  $parameters
//     * @param  \Symfony\Component\Console\Output\OutputInterface|null  $outputBuffer
//     * @return int
//     *
//     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
//     */
//    public function call($command, array $parameters = [], $outputBuffer = null)
//    {
//        [$command, $input] = $this->parseCommand($command, $parameters);
//
//        if (! $this->has($command)) {
//            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $command));
//        }
//
//        return $this->run(
//            $input, $this->lastOutput = $outputBuffer ?: new BufferedOutput
//        );
//    }
//
//    /**
//     * Parse the incoming Artisan command and its input.
//     *
//     * @param  string  $command
//     * @param  array  $parameters
//     * @return array
//     */
//    protected function parseCommand($command, $parameters)
//    {
////        if (is_subclass_of($command, SymfonyCommand::class)) {
////            $callingClass = true;
////
////            $command = $this->laravel->make($command)->getName();
////        }
//
//        if (! isset($callingClass) && empty($parameters)) {
//            $command = $this->getCommandName($input = new StringInput($command));
//        } else {
//            array_unshift($parameters, $command);
//
//            $input = new ArrayInput($parameters);
//        }
//
//        return [$command, $input ?? null];
//    }
//
//    /**
//     * Get the output for the last run command.
//     *
//     * @return string
//     */
//    public function output()
//    {
//        return $this->lastOutput && method_exists($this->lastOutput, 'fetch')
//            ? $this->lastOutput->fetch()
//            : '';
//    }
}
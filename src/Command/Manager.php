<?php

namespace Pff\DatabaseConfig\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Manager extends SymfonyCommand
{
    protected static $defaultName = 'config';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription("数据库主从配置")
            ->addOption('test', 't', InputOption::VALUE_OPTIONAL, 'test')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'config file', 'config.yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile = $input->getOption('file');
        $configFile = (file_exists($configFile) ? '' : getcwd() . DIRECTORY_SEPARATOR) . $configFile;
        if (! file_exists($configFile)) {
            $output->error("No such file: {$configFile}");
            return false;
        }

        $manager = new \Pff\DatabaseConfig\Driver\Manager($configFile, $this->getApplication());

        return $manager->run();
    }

    /**
     * @return SymfonyStyle
     */
    public function output()
    {
        return $this->getApplication()->output();
    }

    /**
     * @return Input|ArgvInput
     */
    public function input()
    {
        return $this->getApplication()->input();
    }
}
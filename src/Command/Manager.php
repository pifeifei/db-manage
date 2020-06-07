<?php

namespace Pff\DatabaseManage\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Manager extends SymfonyCommand
{
    protected static $defaultName = 'db:manage';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription("数据库主从配置")
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, '已有主从，将覆盖现有主从配置。')
            ->addOption('file', '', InputOption::VALUE_OPTIONAL, 'config file', 'config.yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile = $input->getOption('file');
        $configFile = (file_exists($configFile) ? '' : getcwd() . DIRECTORY_SEPARATOR) . $configFile;
        if (! file_exists($configFile)) {
            $output->error("No such file: {$configFile}");
            return false;
        }

        $manager = new \Pff\DatabaseManage\Driver\Manager($configFile, $this->getApplication());

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

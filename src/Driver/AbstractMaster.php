<?php


namespace Pff\DatabaseManage\Driver;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Pff\DatabaseManage\Contracts\Driver\InterfaceMaster;
use Symfony\Component\Console\Input\ArgvInput as SymfonyArgvInput;
use Symfony\Component\Console\Input\Input as SymfonyInput;
use Symfony\Component\Console\Output\Output as SymfonyOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractMaster  implements InterfaceMaster
{
    protected $manager;
    protected $master;
    /* @var array 错误说明 */
    protected $error = [];
    /* @var array 提示说明 */
    protected $notice = [];
    /* @var array */
    protected $sql = [];

    public function __construct($config, Manager $manager)
    {
        $this->manager = $manager;
        $this->master = DriverManager::getConnection($config);
    }

    /**
     * @return Connection
     */
    public function getDB(): Connection
    {
        return $this->master;
    }

    /**
     * @inheritDoc
     */
    abstract public function check();

    /**
     * @inheritDoc
     */
    abstract public function run();

    /**
     * @return SymfonyArgvInput|SymfonyInput
     */
    protected function input()
    {
        return $this->manager->getApplication()->input();
    }

    /**
     * @return SymfonyOutput|SymfonyStyle
     */
    protected function output()
    {
        return $this->manager->getApplication()->output();
    }
}

<?php


namespace Pff\DatabaseConfig\Driver\Mysql;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Pff\DatabaseConfig\Contracts\Driver\InterfaceSlave;
use Pff\DatabaseConfig\Driver\Manager;
use Symfony\Component\Console\Input\ArgvInput as SymfonyArgvInput;
use Symfony\Component\Console\Input\Input as SymfonyInput;
use Symfony\Component\Console\Output\Output as SymfonyOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class Slave implements InterfaceSlave
{
    private $manager;
    private $slave;
    /* @var array 错误说明 */
    protected $error = [];
    /* @var array 提示说明 */
    protected $notice = [];
    /* @var array */
    protected $sql = [];
    /* @var int mysql server_id */
    protected $serverId = 0;

    public function __construct($config, Manager $manager)
    {
        $this->manager = $manager;
        $this->slave = DriverManager::getConnection($config);
    }

    /**
     * @return Connection
     */
    public function getDB(): Connection
    {
        return $this->slave;
    }

    protected function checkVariables()
    {
        $variableNames = [
            'log_bin',
            'server_id',
            'log-bin',
            'skip_networking',
            'sync_binlog',
//            'innodb_flush_log_at_trx_commit',
            'log_slave_updates'
        ];
        $variables = $this->getDB()
            ->query("show variables where Variable_name in ('".implode("','", $variableNames)."')")
            ->fetchAll();
        $variables = array_column($variables, 'Value', 'Variable_name');
        if (! is_numeric($variables['server_id']) || $variables['server_id'] <= 0) {
            $this->error[] = 'slave server_id 必须是大于 0 的整数';
        }

        $this->serverId = intval($variables['server_id']);

        if ('OFF' != strtoupper($variables['skip_networking'])) {
            $this->error[] = 'slave skip_networking 必须设置为 OFF';
        }

        if ('OFF' == strtoupper($variables['log_bin'])) {
            $this->error[] = 'slave log_bin 必须设置，如：binlog，innodb-log 等';
        }
    }

    /**
     * @return int server_id
     */
    public function getServerId()
    {
        if (empty($this->serverId)) {
            $this->checkVariables();
        }
        return $this->serverId;
    }

    public function check()
    {
        $this->checkVariables();
        // TODO: 判断是否是主从。
        $this->notice = array_unique($this->notice);
        $this->sql = array_unique($this->sql);
        return ! empty($this->error);
    }


    public function run()
    {
        $repl = $this->manager->getReplication();
        $master = $this->manager->getMaster();
        $masterBinlog = $master->binlog();

        $sql = [];
        $sql[] = "CHANGE MASTER TO MASTER_HOST='".$repl->getMasterHost()."',MASTER_PORT=".$repl->getMasterPort().", MASTER_USER='".$repl->getUser()."', "
               . "MASTER_PASSWORD='".$repl->getPassword()."', MASTER_LOG_FILE='".$masterBinlog['File']."'";
        $sql[] = 'start slave;';

        foreach ($sql as $query) {
            $this->getDB()->query($query);
        }
    }

    public function getParams()
    {
        return $this->getDB()->getParams();
    }

    public function getKey()
    {
        $params = $this->getParams();
        return "{$params['host']}:{$params['port']}";
    }

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
<?php

namespace Pff\DatabaseManage\Driver\MySql;

use Doctrine\DBAL\DBALException;
use Pff\DatabaseManage\Driver\AbstractReplication;
use Pff\DatabaseManage\Driver\AbstractSlave;

class Slave extends AbstractSlave
{
//    private $manager;
//    private $slave;
//    /* @var array 错误说明 */
//    protected $error = [];
//    /* @var array 提示说明 */
//    protected $notice = [];
//    /* @var array */
//    protected $sql = [];
    /* @var int mysql server_id */
    protected $serverId = 0;

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
            ->query("show variables where Variable_name in ('" . implode("','", $variableNames) . "')")
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

    /**
     * 判断是否启动复制
     * @return bool
     * @throws DBALException
     */
    protected function isReplicationRun()
    {
        $slaveStatus = $this->getDB()->query($this->getReplication()->buildShowSlaveStatusSql())->fetch();
        if (empty($slaveStatus)) {
            return false;
        }

        if (empty($slaveStatus['Slave_IO_State'])) {
            return false;
        }

        return true;
    }

    public function run()
    {
        $repl = $this->getReplication();

        $sql = [];

        if ($this->isReplicationRun()) {
            $sql[] = $repl->buildStopSlaveSql();
        }
        $sql[] = $repl->buildSlaveChangeMasterSql();
        $sql[] = $repl->buildStartSlaveSql();
        foreach ($sql as $query) {
            $this->getDB()->query($query);
        }
    }

    /**
     * @return \Pff\DatabaseManage\Contracts\Replication|AbstractReplication|Replication
     */
    protected function getReplication()
    {
        return $this->manager->getReplication();
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
}

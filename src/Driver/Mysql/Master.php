<?php


namespace Pff\DatabaseConfig\Driver\Mysql;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Pff\DatabaseConfig\Contracts\Driver\InterfaceMaster;
use Pff\DatabaseConfig\Driver\Manager;
use Symfony\Component\Console\Input\ArgvInput as SymfonyArgvInput;
use Symfony\Component\Console\Input\Input as SymfonyInput;
use Symfony\Component\Console\Output\Output as SymfonyOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class Master implements InterfaceMaster
{
    private $manager;
    private $master;
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
        $this->master = DriverManager::getConnection($config);
    }

    /**
     * @return Connection
     */
    public function getDB(): Connection
    {
        return $this->master;
    }

    protected function checkVariables()
    {
        $variableNames = [
            'log_bin',
            'server_id',
            'log-bin',
            'skip_networking',
            'sync_binlog',
            'innodb_flush_log_at_trx_commit',
            'log_slave_updates'
        ];

        $variables = $this->getDB()->query("show variables where Variable_name in ('".implode("','", $variableNames)."')")->fetchAll();
        $variables = array_column($variables, 'Value', 'Variable_name');
        if (! is_numeric($variables['server_id']) || $variables['server_id'] <= 0) {
            $this->error[] = 'master server_id 必须是大于 0 的整数';
        }
        $this->serverId = intval($variables['server_id']);
        if (1 != $variables['innodb_flush_log_at_trx_commit']) {
            $this->error[] = 'master innodb_flush_log_at_trx_commit 必须设置为 1';
        }
        if (1 != $variables['sync_binlog']) {
            $this->error[] = 'master sync_binlog 必须设置为 1';
        }
        if ('OFF' != strtoupper($variables['skip_networking'])) {
            $this->error[] = 'master skip_networking 必须设置为 OFF';
        }
        if ('ON' != strtoupper($variables['log_slave_updates'])) {
            $this->error[] = 'master log_slave_updates 必须设置为 ON';
        }
        if ('OFF' == strtoupper($variables['log_bin'])) {
            $this->error[] = 'master log_bin 必须设置，如：binlog，innodb-log 等';
        }
    }

    protected function hasReplicationUser()
    {
        $repl = $this->manager->getReplication();
        $sql = "select count(*) ct from `mysql`.user where `user`='".$repl->getUser()."' and Host = '".$repl->getHost()."'";
        $count = $this->getDB()->query($sql)->fetch();

        if ($count['ct'] > 0) {
            return true;
        }

        return false;
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

    protected function getKey()
    {
        $params = $this->getParams();
        return "{$params['host']}:{$params['port']}";
    }

    /**
     * @return Slave[]
     */
    protected function getSlaves()
    {
        return $this->manager->getSlaves();
    }

    protected function checkServerId()
    {
        $serverIds = [$this->getKey() => $this->getServerId()];
        $slaves = $this->getSlaves();
        foreach ($slaves as $slave){
            $serverId = $slave->getServerId();
            if (false !== array_search($serverId, $serverIds)) {
                $this->output()->error("server id 冲突，" . $this->getKey() . ','.$slave->getKey());
                exit;
            }
            $serverIds[$slave->getKey()] = $serverId;
        }
    }

    public function check()
    {
        $this->checkVariables();
        $this->checkServerId();
        if ($this->hasReplicationUser()) {
            $repl = $this->manager->getReplication();
            $this->notice[] = '主从复制密码将被重置！';
            $this->sql[] = "set password for '".$repl->getUser()."'@'".$repl->getHost()."' = password('".$repl->getPassword()."')";
        }

        $this->notice = array_unique($this->notice);
        $this->sql = array_unique($this->sql);
        return ! empty($this->error);
    }

    public function hasNotice()
    {
        return ! empty($this->notice);
    }

    /**
     * @return bool
     */
    public function run()
    {
        if ($this->check()) {
            $this->output()->warning($this->error);
            return false;
        }

//        $c = $this->output()->confirm('要执行嘻嘻嘻，确定要执行吗？', true);

        if ($this->hasNotice()) {
            $this->output()->caution($this->notice);
            $this->output()->caution($this->sql);
            $result = $this->output()->confirm('需要重置主从用户密码，确定要执行吗？', true);
            if (! $result) {
                exit;
            }
        }


        foreach ($this->buildSql() as $sql) {
            $this->getDB()->query($sql);
        }
        // 锁定 mysql
        $this->getDB()->query('FLUSH TABLES WITH READ LOCK');

        $slaves = $this->manager->getSlaves();
        foreach ($slaves as $slave) {
            $slave->run();
        }
        $this->getDB()->query('UNLOCK TABLES');
        $repl = $this->manager->getReplication();
        $this->output()->success([
            "主从复制用户：".$repl->getUser()."，密码是：" . $repl->getPassword(),
            'done'
        ]);

        // 验证从库
        // FLUSH TABLES WITH READ LOCK;
        // UNLOCK TABLES;
        // show master status;
        //

        return true;
        // master 配置
        // slave 配置
//        show variable where Variable_name in ('server_id', 'log-bin', 'skip_networking', 'sync_binlog', 'innodb_flush_log_at_trx_commit', 'log_slave_updates');
        //master must set: skip_networking=off , sync_binlog=1, innodb_flush_log_at_trx_commit =1
//        select count(*) from mysql.user where `user`='rep' and Host = '%';
//        set password for rep@localhost = password('123');
//        create user 'rep'@'%' identified by 'Sync!0000';
//        grant FILe on *.* to 'rep'@'%' identified by 'Sync!0000';
//        grant replication slave on *.* to 'rep'@'%' identified by 'Sync!0000';
//        flush privileges;
    }

    protected function buildSql()
    {
        $repl = $this->manager->getReplication();
        $user = $repl->getUser();
        $password = $repl->getPassword();
        $host = $repl->getHost();

        $sql = $this->sql;
        if (! $this->hasReplicationUser()) {
            $sql[] = "create user '{$user}'@'{$host}' identified by '{$password}'";
        }

        $sql[] = "grant FILe on *.* to '{$user}'@'{$host}' identified by '{$password}';";
        $sql[] = "grant replication slave on *.* to '{$user}'@'{$host}' identified by '{$password}'";
        $sql[] = "flush privileges";
        return $sql;
    }

    public function binlog()
    {
        static $binlog;
        if (empty($binlog)) {
            $binlog = $this->getDB()->query('show master status')->fetch();
        }
        return $binlog;
    }

    public function getParams()
    {
        return $this->getDB()->getParams();
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
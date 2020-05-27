<?php

namespace Pff\DatabaseManage\Driver\MySql;

use Pff\DatabaseManage\Driver\AbstractReplication;

class Replication extends AbstractReplication
{
    public function getChannel()
    {
        return $this->config['channel'] ?? '';
    }

    public function BuildSetPasswordSql()
    {
        return "set password for '" . $this->getUser() . "'@'" . $this->getHost() . "' = password('" . $this->getPassword() . "')";
    }
    public function buildReplicationSql()
    {
        return "create user '{$this->getUser()}'@'{$this->getHost()}' identified by '{$this->getPassword()}'";
    }

    public function buildGrantFileSql()
    {
        return "grant FILe on *.* to '{$this->getUser()}'@'{$this->getHost()}' identified by '{$this->getPassword()}';";
    }

    public function buildGrantReplicationSql()
    {
        return "grant replication slave on *.* to '{$this->getUser()}'@'{$this->getHost()}' identified by '{$this->getPassword()}'";
    }

    public function buildFlushPrivilegesSql()
    {
        return 'flush privileges';
    }

    public function buildSlaveChangeMasterSql()
    {
        $masterBinlog = $this->getMaster()->binlog();
        return sprintf(
            "CHANGE MASTER TO MASTER_HOST='%s',MASTER_PORT=%d, MASTER_USER='%s', MASTER_PASSWORD='%s', "
            . "MASTER_LOG_FILE='%s', MASTER_LOG_POS=%d FOR channel '%s'",
            $this->getMasterHost(),
            $this->getMasterPort(),
            $this->getUser(),
            $this->getPassword(),
            $masterBinlog['File'],
            $masterBinlog['Position'],
            $this->getChannel()
        );
//        return "CHANGE MASTER TO MASTER_HOST='".$this->getMasterHost()."',MASTER_PORT=".$this->getMasterPort()
//            .", MASTER_USER='".$this->getUser()."', " . "MASTER_PASSWORD='".$this->getPassword()
//            ."', MASTER_LOG_FILE='".$masterBinlog['File']."', MASTER_LOG_POS='".$masterBinlog['Position']
//            ."' FOR channel '{$this->getChannel()}'";
    }

    public function buildStartSlaveSql()
    {
        return "start slave for channel '{$this->getChannel()}'";
    }

    public function buildStopSlaveSql()
    {
        return "stop slave for channel '{$this->getChannel()}'";
    }

    public function buildShowSlaveStatusSql()
    {
        return "show slave status for channel '{$this->getChannel()}'";
    }
}

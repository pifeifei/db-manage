<?php

namespace Pff\DatabaseManage\Driver;

use Pff\DatabaseManage\Contracts\Console\Application;
use Pff\DatabaseManage\Contracts\Driver\InterfaceMaster;
use Pff\DatabaseManage\Contracts\Driver\InterfaceSlave;
use Pff\DatabaseManage\Contracts\Replication;
use Pff\DatabaseManage\Driver\MySql\Master as MySqlMaster;
use Pff\DatabaseManage\Driver\MySql\Slave as MySqlSlave;
use Pff\DatabaseManage\Driver\MySql\Replication as MySqlReplication;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Yaml\Yaml;

final class Manager
{
    /* @var Application */
    protected $symfonyApplication;

    /* @var array  see config.yaml.sample */
    protected $config;

    /**
     * @var Replication
     */
    protected $replication;

    /**
     * @var InterfaceMaster|AbstractMaster
     */
    protected $master;

    /**
     * @var InterfaceSlave[]|AbstractSlave[]
     */
    protected $slaves;

    private static $replicationMap = [
        'pdo_mysql'          => MySqlReplication::class,
//        'pdo_sqlite'         => PDOSQLiteDriver::class,
//        'pdo_pgsql'          => PDOPgSQLDriver::class,
// //        'pdo_oci'            => PDOOCIDriver::class,
//        'oci8'               => OCI8Driver::class,
//        'ibm_db2'            => DB2Driver::class,
//        'pdo_sqlsrv'         => PDOSQLSrvDriver::class,
// //        'mysqli'             => MySQLiDriver::class,
//        'drizzle_pdo_mysql'  => DrizzlePDOMySQLDriver::class,
//        'sqlanywhere'        => SQLAnywhereDriver::class,
// //        'sqlsrv'             => SQLSrvDriver::class,
    ];

    private static $masterMap = [
        'pdo_mysql'          => MySqlMaster::class,
//        'pdo_sqlite'         => PDOSQLiteDriver::class,
//        'pdo_pgsql'          => PDOPgSQLDriver::class,
// //        'pdo_oci'            => PDOOCIDriver::class,
//        'oci8'               => OCI8Driver::class,
//        'ibm_db2'            => DB2Driver::class,
//        'pdo_sqlsrv'         => PDOSQLSrvDriver::class,
// //        'mysqli'             => MySQLiDriver::class,
//        'drizzle_pdo_mysql'  => DrizzlePDOMySQLDriver::class,
//        'sqlanywhere'        => SQLAnywhereDriver::class,
// //        'sqlsrv'             => SQLSrvDriver::class,
    ];

    private static $slaveMap = [
        'pdo_mysql'          => MySqlSlave::class,
//        'pdo_sqlite'         => PDOSQLiteDriver::class,
//        'pdo_pgsql'          => PDOPgSQLDriver::class,
// //        'pdo_oci'            => PDOOCIDriver::class,
//        'oci8'               => OCI8Driver::class,
//        'ibm_db2'            => DB2Driver::class,
//        'pdo_sqlsrv'         => PDOSQLSrvDriver::class,
// //        'mysqli'             => MySQLiDriver::class,
//        'drizzle_pdo_mysql'  => DrizzlePDOMySQLDriver::class,
//        'sqlanywhere'        => SQLAnywhereDriver::class,
// //        'sqlsrv'             => SQLSrvDriver::class,
    ];

    private static $driverAlias = [
        'mysql' => 'pdo_mysql',
        'mysql2' => 'pdo_mysql',
        'mysqli' => 'pdo_mysql',
//        'pdo_oci' => 'oci8',
//        'sqlsrv' => 'pdo_sqlsrv',
//        'db2' => 'ibm_db2',
//        'mssql' => 'pdo_sqlsrv',
//        'pgsql' => 'pdo_pgsql',
//        'postgres' => 'pdo_pgsql',
//        'postgresql' => 'pdo_pgsql',
//        'sqlite' => 'pdo_sqlite',
//        'sqlite3' => 'pdo_sqlite',
    ];


    public function __construct($configFile, SymfonyApplication $symfonyApplication)
    {
        $config = Yaml::parseFile($configFile);
        $config['default']['driver'] = $config['driver'];
        $config['master'] = array_merge($config['default'], $config['master']);
        foreach ($config['slaves'] as & $slave) {
            $slave = array_merge($config['default'], $slave);
        }
        unset($config['default'], $config['driver']);
        $this->config = $config;

        $this->bootstrap();
        $this->symfonyApplication = $symfonyApplication;
    }

    protected function bootstrap()
    {
        $this->createReplication();
        $this->createMaster();
        $this->createSlaves();
    }

    /**
     * @return string
     * @throws DBDriverException
     */
    private function getDriverName()
    {
        $driver = $this->getConfig('master');
        $driverName = $driver['driver'];
        if (isset(self::$masterMap[$driverName])) {
            return $driverName;
        }

        if (isset(self::$driverAlias[$driverName])) {
            return $driverName = self::$driverAlias[$driverName];
        }

        throw DBDriverException::unknownDriver($driverName, array_keys(self::$masterMap));
    }

    private function getReplicationDriver()
    {
        $driver = $this->getDriverName();
        return self::$replicationMap[$driver];
    }

    private function createReplication()
    {
        $config = $this->getConfig('replication');
        $class = $this->getReplicationDriver();
        $this->replication = new $class($config, $this);
    }

    /**
     * @return Replication|AbstractReplication
     */
    public function getReplication()
    {
        return $this->replication;
    }

    private function getMasterDriver()
    {
        $driver = $this->getDriverName();
        return self::$masterMap[$driver];
    }
    private function createMaster()
    {
        $class = $this->getMasterDriver();
        $config = $this->getConfig('master');
        return $this->master = new $class($config, $this);
    }

    private function getSlaveDriver()
    {
        $driver = $this->getDriverName();
        return self::$slaveMap[$driver];
    }
    private function createSlaves()
    {
        $slaves = $this->getConfig('slaves');
        if (empty($slaves)) {
            throw new DBDriverException('请配置从库');
        }
        $master = $this->getMaster()->getParams();
        $masterKey = "{$master['host']}:{$master['port']}";
        $class = $this->getSlaveDriver();
        foreach ($slaves as $slave) {
            $key = "{$slave['host']}:{$slave['port']}";
            if (isset($this->slaves[$key]) || $key === $masterKey) {
                throw new DBDriverException('从库配置重复:' . $key);
            }
            $this->slaves[$key] = new $class($slave, $this);
        }
    }

    public function getApplication()
    {
        return $this->symfonyApplication;
    }

    /**
     * @return int
     * @throws \Throwable
     */
    public function run()
    {
            $this->getMaster()->run();

//            $this->checkMasterAndSlaves();
//            $result = $this->getMaster()->getDB()->query('show  variables like "%master%";');
//            dump($result->fetchAll());
//        $result = $conn->query('show  variables where Variable_name in("server_id", "server_uuid");');
//        dump($result->fetchAll());
//        dump($this->config['master']);
//        try {
//        } catch (\Throwable $t) {
//            $output = $this->getApplication()->output();
//            $output->error(iconv('GBK','UTF-8',$t->getMessage()));
//
//            return 1;
//        }

        return 0;
    }

    /**
     * @param null $name options: master, slaves, replication
     * @return array
     */
    public function getConfig($name = null)
    {
        return is_null($name) ? $this->config : $this->config[$name];
    }

    /**
     * @return InterfaceMaster|AbstractMaster
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * @return InterfaceSlave[]|AbstractSlave[]
     */
    public function getSlaves()
    {
        return $this->slaves;
    }
}

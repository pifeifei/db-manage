<?php


namespace Pff\DatabaseConfig\Driver;



use Pff\DatabaseConfig\Contracts\Console\Application;
use Pff\DatabaseConfig\Driver\Mysql\Master;
use Pff\DatabaseConfig\Driver\Mysql\Slave;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Yaml\Yaml;

class Manager
{
    /* @var Master */
    protected static $instance;

    /* @var Application */
    protected $symfonyApplication;

    /* @var array  see config.yaml.sample */
    protected $config;

    /**
     * @var \Pff\DatabaseConfig\Contracts\Replication
     */
    protected $replication;

    /**
     * @var Master
     */
    protected $master;

    /**
     * @var Slave[]
     */
    protected $slaves;
//    protected $relationship = [
//        'pdo_mysql' => 'Mysql',
//        'pdo_sqlite' => 'Sqlite',
//    ];

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
        self::$instance = $this;
    }

    protected function bootstrap()
    {
        $this->replication();
        $this->createMaster();
        $this->createSlaves();
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            echo 'xxx';
            exit;
        }

        return self::$instance;
    }

    private function replication()
    {
        $repl = $this->getConfig('replication');
        $this->replication = new Replication($repl, $this);
    }

    public function getReplication()
    {
        return $this->replication;
    }

    private function createMaster()
    {
        $this->master = new Master($this->getConfig('master'), $this);
    }

    private function createSlaves()
    {
        $slaves = $this->getConfig('slaves');
        if (empty($slaves)) {
            throw new DBDriverException('请配置从库');
        }
        $master = $this->getMaster()->getParams();
        $masterKey = "{$master['host']}:{$master['port']}";
        foreach ($slaves as $slave) {
            $key = "{$slave['host']}:{$slave['port']}";
            if (isset($this->slaves[$key]) || $key === $masterKey){
                throw new DBDriverException('从库配置重复:' . $key);
            }
            $this->slaves[$key] = new Slave($slave, $this);
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


    public function getMaster()
    {
        return $this->master;
    }

    /**
     * @return Slave[]
     */
    public function getSlaves()
    {
        return $this->slaves;
    }
}
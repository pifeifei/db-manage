<?php


namespace Pff\DatabaseManage\Driver;

use Pff\DatabaseManage\Contracts\Replication as InterfaceReplication;
use Pff\DatabaseManage\Driver\AbstractMaster as Master;
use Pff\DatabaseManage\Support\Str;

abstract class AbstractReplication implements InterfaceReplication
{
    use Traits\SymfonyCommand;

    /** @var Master */
    protected $manager;
    /* @var array */
    protected $config = [
        'user' => 'repl',
        'password' => true, // true:随机密码，或密码字符串
        'host' => '%', // 需适用所有从库
//        'channel' => '',
    ];

    public function __construct(array $config, Manager $manager)
    {
        $this->config = array_merge($this->config, $config);
        $this->manager = $manager;

        if (true === $this->getPassword()) {
            $this->randomPassword();
        }
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->config['user'];
    }

    /**
     * @param string $user
     * @return AbstractReplication
     */
    public function setUser(string $user): AbstractReplication
    {
        $this->config['user'] = $user;
        return $this;
    }

    /**
     * @return string|bool
     */
    public function getPassword()
    {
        $password = $this->config['password'];
        return is_bool($password) ? $password : (string)$password;
    }

    /**
     * @param string $password
     * @return AbstractReplication
     */
    public function setPassword(string $password): AbstractReplication
    {
        $this->config['password'] = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->config['host'];
    }

    /**
     * @param string $host
     * @return AbstractReplication
     */
    public function setHost(string $host): AbstractReplication
    {
        $this->config['host'] = $host;
        return $this;
    }

    /**
     * reset password
     *
     * @return AbstractReplication
     */
    public function randomPassword(): AbstractReplication
    {
        $this->config['password'] = Str::random(30);
        return $this;
    }

    public function getMaster()
    {
        return $this->manager->getMaster();
    }

    public function getMasterHost()
    {
        $master = $this->getMaster();
        return $master->getParams()['host'];
    }

    public function getMasterPort()
    {
        $master = $this->getMaster();
        return $master->getParams()['port'];
    }
}

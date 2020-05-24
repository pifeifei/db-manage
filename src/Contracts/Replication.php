<?php


namespace Pff\DatabaseConfig\Contracts;


use Pff\DatabaseConfig\Driver\Manager;

interface Replication
{
    /**
     * Replicating constructor.
     * @param array $config
     * @param Manager $manager
     */
    public function __construct(array $config, Manager $manager);

    public function getUser();

    public function getPassword();

    public function randomPassword();

    public function getHost();
}

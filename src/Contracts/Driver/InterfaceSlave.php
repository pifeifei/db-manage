<?php


namespace Pff\DatabaseConfig\Contracts\Driver;


interface InterfaceSlave
{
    public function check();
    public function run();
}
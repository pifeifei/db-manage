<?php


namespace Pff\DatabaseConfig\Contracts\Driver;


interface InterfaceMaster
{
    public function check();
    public function run();
}
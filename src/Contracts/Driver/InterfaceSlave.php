<?php


namespace Pff\DatabaseManage\Contracts\Driver;


interface InterfaceSlave
{
    public function check();
    public function run();
}
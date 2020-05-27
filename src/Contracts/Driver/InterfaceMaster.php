<?php


namespace Pff\DatabaseManage\Contracts\Driver;


interface InterfaceMaster
{
    /**
     * @return bool
     */
    public function check();

    /**
     * @return bool
     */
    public function run();
}
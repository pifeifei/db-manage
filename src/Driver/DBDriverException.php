<?php


namespace Pff\DatabaseManage\Driver;


use Exception;

class DBDriverException extends Exception
{
    /**
     * @param string   $unknownDriverName
     * @param string[] $knownDrivers
     *
     * @return DBDriverException
     */
    public static function unknownDriver($unknownDriverName, array $knownDrivers)
    {
        return new self("The given 'driver' " . $unknownDriverName . ' is unknown, ' .
            'db:manage currently supports only the following drivers: ' . implode(', ', $knownDrivers));
    }
}

<?php

/* 
 * Class for interfacing with the guest table.
 */

class DiskTable extends \iRAP\MysqlObjects\AbstractTable
{
    public function getDb(): \mysqli { return ConnectionHandler::getConnection(); }
    public function validateInputs(array $data): array { return $data; }
    
    public function getFieldsThatAllowNull(): array 
    { 
        return array(); 
    }

    public function getFieldsThatHaveDefaults(): array
    {
        return array();
    } 

    public function getObjectClassName() { return "Disk"; }
    public function getTableName() { return "disk"; }
}
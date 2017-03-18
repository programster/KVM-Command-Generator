<?php

/* 
 * Class for interfacing with the guest table.
 */

class GuestDiskTable extends \iRAP\MysqlObjects\AbstractTable
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

    public function getObjectClassName() { return "GuestDisk"; }
    public function getTableName() { return "guest_disk"; }
}
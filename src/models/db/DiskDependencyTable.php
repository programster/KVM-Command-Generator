<?php

/* 
 * Class for interfacing with the disk_dependency table.
 */

class DiskDependencyTable extends \iRAP\MysqlObjects\AbstractTable
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

    public function getObjectClassName() { return "DiskDependency"; }
    public function getTableName() { return "disk_dependency"; }
}
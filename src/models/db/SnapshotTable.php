<?php

/* 
 * Class for interfacing with the snapshot table.
 */

class SnapshotTable extends \iRAP\MysqlObjects\AbstractTable
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

    public function getObjectClassName() { return "Snapshot"; }
    public function getTableName() { return "snapshot"; }
}
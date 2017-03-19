<?php

/* 
 * Class for instantiating objects representing a row in the disk table.
 */

class Disk extends \iRAP\MysqlObjects\AbstractTableRowObject
{
    private $m_path;
    
    public function __construct(array $data, $row_field_types=null) 
    {
        $this->initializeFromArray($data,$row_field_types);
    }
    
    protected function getAccessorFunctions(): array
    {
        return array(
            'path' => function() { return $this->m_path; }
        );
    }
    
    protected function getSetFunctions(): array 
    {
        return array(
            'path' => function($x){ $this->m_path = $x; }
        );
    } 
    
    public function getTableHandler(): \iRAP\MysqlObjects\TableInterface 
    {
        return DiskTable::getInstance();
    }
    
    
    # Accessors
    public function get_path() { return $this->m_path; }
}
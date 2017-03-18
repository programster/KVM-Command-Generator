<?php

/* 
 * Class for instantiating objects representing a row in the disk_dependency table.
 */

class DiskDependency extends \iRAP\MysqlObjects\AbstractTableRowObject
{
    private $m_parent_disk_id;
    private $m_child_disk_id;
    
    public function __construct(array $data, $row_field_types=null) 
    {
        $this->initializeFromArray($data,$row_field_types);
    }
    
    protected function getAccessorFunctions(): array
    {
        return array(
            'parent_disk_id' => function() { return $this->m_parent_disk_id; },
            'child_disk_id' => function() { return $this->m_child_disk_id; }
        );
    }
    
    protected function getSetFunctions(): array 
    {
        return array(
            'parent_disk_id' => function($x){ $this->m_parent_disk_id = $x; },
            'child_disk_id' => function($x) { $this->m_child_disk_id = $x; }
        );
    } 
    
    public function getTableHandler(): \iRAP\MysqlObjects\TableInterface 
    {
        return DiskDependencyTable::getInstance();
    }
}
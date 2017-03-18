<?php

/* 
 * Class for instantiating objects representing a row in the guest table.
 */

class Snapshot extends \iRAP\MysqlObjects\AbstractTableRowObject
{
    private $m_name;
    private $m_guest_id;
    private $m_disk_id;
    private $m_mem_path;
    
    public function __construct(array $data, $row_field_types=null) 
    {
        $this->initializeFromArray($data,$row_field_types);
    }
    
    protected function getAccessorFunctions(): array
    {
        return array(
            'name'      => function() { return $this->m_name; },
            'guest_id'  => function() { return $this->m_guest_id; },
            'disk_id'   => function() { return $this->m_disk_id; },
            'mem_path'  => function() { return $this->m_mem_path; }
        );
    }
    
    protected function getSetFunctions(): array 
    {
        return array(
            'name'      => function($x) { $this->m_name = $x; },
            'guest_id'  => function($x) { $this->m_guest_id = $x; },
            'disk_id'   => function($x) { $this->m_disk_id = $x; },
            'mem_path'  => function($x) { $this->m_mem_path = $x; }
        );
    } 
    
    public function getTableHandler(): \iRAP\MysqlObjects\TableInterface 
    {
        return SnapshotTable::getInstance();
    }
    
    
    # Accessors
    public function get_name()     { return $this->m_name; }
    public function get_guest_id() { return $this->m_guest_id; }
    public function get_disk_id()  { return $this->m_disk_id; }
    public function get_mem_path() { return $this->m_mem_path; }
}
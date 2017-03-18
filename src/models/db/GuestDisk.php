<?php

/* 
 * Class for instantiating objects representing a row in the guest table.
 */

class GuestDisk extends \iRAP\MysqlObjects\AbstractTableRowObject
{
    private $m_guest_id;
    private $m_disk_id;
    
    public function __construct(array $data, $row_field_types=null) 
    {
        $this->initializeFromArray($data,$row_field_types);
    }
    
    protected function getAccessorFunctions(): array
    {
        return array(
            'guest_id'  => function() { return $this->m_guest_id; },
            'disk_id'   => function() { return $this->m_disk_id; }
        );
    }
    
    protected function getSetFunctions(): array 
    {
        return array(
            'guest_id'  => function($x) { $this->m_guest_id = $x; },
            'disk_id'   => function($x) { $this->m_disk_id = $x; }
        );
    } 
    
    public function getTableHandler(): \iRAP\MysqlObjects\TableInterface 
    {
        return GuestDiskTable::getInstance();
    }
}
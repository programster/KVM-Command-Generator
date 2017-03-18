<?php

/* 
 * Class for instantiating objects representing a row in the guest table.
 */

class Guest extends \iRAP\MysqlObjects\AbstractTableRowObject
{
    private $m_name;
    
    public function __construct(array $data, $row_field_types=null) 
    {
        $this->initializeFromArray($data, $row_field_types);
    }
    
    protected function getAccessorFunctions(): array
    {
        return array(
            'name' => function() { return $this->m_name; }
        );
    }
    
    protected function getSetFunctions(): array 
    {
        return array(
            'name' => function($x){ $this->m_name = $x; }
        );
    } 
    
    public function getTableHandler(): \iRAP\MysqlObjects\TableInterface 
    {
        return GuestTable::getInstance();
    }
    
    # Accessors
    public function get_name() { return $this->m_name; }
}
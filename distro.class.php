<?php

class Distro
{
    private $m_name;
    private $m_osVariant;
    private $m_isoLocation;

    public function __construct($name, $osVariant, $isoLocation)
    {
        $this->m_name = $name;
        $this->m_osVariant = $osVariant;
        $this->m_isoLocation = $isoLocation;
    }

    public function getName()        { return $this->m_name; }
    public function getOsVariant()   { return $this->m_osVariant; }
    public function getIsoLocation() { return $this->m_isoLocation; }
}
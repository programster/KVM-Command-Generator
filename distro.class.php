<?php

/**
 * Class for representing a suppoted distro to deploy.
 * osVariant can be looked up from the command: "osinfo-query os" 
 * after having installed the "libosinfo-bin" package
 *
 */

class Distro
{
    private $m_name;
    private $m_osVariant;
    private $m_location;
    private $m_kickstart;
    private $m_extraArgs;
    private $m_kickstartArgKeyword;

    public function __construct($name, 
                                $osVariant, 
                                $location, 
                                $kickstartLoc, 
                                $kickstartArgKeyword, 
                                $extraArgs="")
    {
        $this->m_name                = $name;
        $this->m_osVariant           = $osVariant;
        $this->m_location            = $location;
        $this->m_kickstart           = $kickstartLoc;
        $this->m_kickstartArgKeyword = $kickstartArgKeyword;
        $this->m_extraArgs           = $extraArgs;
    }
    
    public function getName()                { return $this->m_name; }
    public function getOsVariant()           { return $this->m_osVariant; }
    public function getLocation()            { return $this->m_location; }
    public function getKickstartUrl()        { return $this->m_kickstart; }
    public function getKickstartArgKeyword() { return $this->m_kickstartArgKeyword; }
    public function getExtraArgs()           { return $this->m_extraArgs; }
}
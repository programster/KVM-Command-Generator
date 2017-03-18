<?php

/*
 * Class for representing a supported Distro to deploy from scratch using a kickstart file.
 * Once you have deployed a distro or two, you probably want to just clone the VMs for future VMs
 * as the installation process is quite slow.
 */

class Distro implements JsonSerializable
{
    private $m_name;
    private $m_osVariant;
    private $m_distroLocation;
    private $m_kickstart;
    private $m_extraArgs;
    private $m_kickstartArgKeyword;
    
    
    /**
     * Constructor for a Distro.
     * @param string $name - a name for users to choose this distro by. e.g. centos-with-docker
     * @param string $osVariant - the os_variant keyword that kvm will recognize. osVariant can be 
     *                            looked up from the command: "osinfo-query os" after having 
     *                            installed the "libosinfo-bin" package.
     * @param string $location - the location of the distro source. E.g. an ftp or http address
     * @param string $kickstartLoc - the location of the kickstart file for automatic installation.
     * @param string $kickstartArgKeyword - I forget...
     * @param string $extraArgs - optional extra arguments.
     */
    public function __construct($name, 
                                $osVariant, 
                                $location, 
                                $kickstartLoc, 
                                $kickstartArgKeyword, 
                                $extraArgs="")
    {
        $this->m_name                = $name;
        $this->m_osVariant           = $osVariant;
        $this->m_distroLocation      = $location;
        $this->m_kickstart           = $kickstartLoc;
        $this->m_kickstartArgKeyword = $kickstartArgKeyword;
        $this->m_extraArgs           = $extraArgs;
    }
    
    
    /**
     * Create/load a single Distro from an array representation of this object.
     * E.g. the opposite of jsonSerialize
     * @param array $input
     * @return \Distro
     */
    public static function loadFromArray(array $input)
    {
        $extraArgs = "";
        
        if (isset($input["extra_args"]))
        {
            $extraArgs = $input["extra_args"];
        }
        
        return new Distro(
            $input["name"],
            $input["os_variant"],
            $input["distro_location"],
            $input["kickstart_location"],
            $input["kickstart_arg_keyword"],
            $extraArgs
        );
    }
    
    
    /**
     * Serialize the distro into an array so we can save it to a file.
     * @return array
     */
    public function jsonSerialize() 
    {
        return array(
            "name" => $this->m_name,
            "os_variant" => $this->m_osVariant,
            "distro_location" => $this->m_distroLocation,
            "kickstart_location" => $this->m_kickstart,
            "extra_args" => $this->m_extraArgs,
            "kickstart_arg_keyword" => $this->m_kickstartArgKeyword
        );
    }
    
    
    // Accessors
    public function getName()                { return $this->m_name; }
    public function getOsVariant()           { return $this->m_osVariant; }
    public function getLocation()            { return $this->m_distroLocation; }
    public function getKickstartUrl()        { return $this->m_kickstart; }
    public function getKickstartArgKeyword() { return $this->m_kickstartArgKeyword; }
    public function getExtraArgs()           { return $this->m_extraArgs; }
}
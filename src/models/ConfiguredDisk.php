<?php

/* 
 * Class for a configured disk that a new VM will have.
 */
class ConfiguredDisk
{
    private $m_filepath;
    private $m_diskFormat;
    private $m_cacheMode;
    private $m_size;
    
    
    /**
     * Configure all the various storage/disk aspects of the VM by asking the user questions.
     * @param Array $switches
     * @param String $vmName
     * @return Array
     */
    public function __construct($vmName)
    {
        $this->m_size = intval(App::getInput("How much allocated storage (in GB)?"));
        
        while ($this->m_size < 1)
        {
            print "Disk must be at least 1 GB" . PHP_EOL;
            $this->m_size = intval(App::getInput("How much allocated storage (in GB)?"));
        }
        
        
        $formatMenu = new \Programster\CliMenu\ValueMenu("Disk Format");
        $formatMenu->addOption("Qcow2 (recommended)", "qcow2");
        $formatMenu->addOption("Raw", "raw");
        $this->m_diskFormat = $formatMenu->run();
        
        $this->m_filepath = KVM_DIR . '/' . $vmName . '/disk.' . $this->m_diskFormat;
        
        // For more info on the types:
        // https://www.ibm.com/support/knowledgecenter/linuxonibm/liaat/liaatbpkvmguestcache.htm
        $cacheModeMenu = new \Programster\CliMenu\ValueMenu("Disk Cache Mode");
        $cacheModeMenu->addOption("Writethrough (recommended)", "writethrough");
        $cacheModeMenu->addOption("None (for NFS based storage)", "none");
        $cacheModeMenu->addOption("Writeback (use with caution)", "writeback");
        $this->m_cacheMode = $cacheModeMenu->run();
    }
    
    
    // Accessors
    public function getSize() { return $this->m_size; }
    public function getFilepath() { return $this->m_filepath; }
    public function getCacheMode() { return $this->m_cacheMode; }
    public function getFormat() { return $this->m_diskFormat; }
}

<?php

/* 
 * 
 */

class VirtualMachine
{
    private $m_name; // name for the VM.
    private $m_vCpuCount;
    private $m_ram;
    private $m_distro; /* @var $m_distro Distro */
    private $m_installationSource;
    private $m_configuredDisk; /* @var $m_configuredDisk ConfiguredDisk */
    private $m_installVmCommand;
    
    
   /**
    * Ask the user for what distro they wish to deploy and grab it if we do not already have it.
    * This also sets up the switches that depend on the distro specified
    * @param Array $switches
    * @return Array - the modified switches
    */
    public function __construct()
    {
        $this->m_name = App::getInput("Name for the VM?");
        
        $optionalDistros = App::loadDistrosFromFile();
        $distroMenu = new \Programster\CliMenu\ValueMenu("Distros");
        
        foreach ($optionalDistros as $distro)
        {
            /* @var $distro Distro */
            $distroMenu->addOption($distro->getName(), $distro);
        }
        
        $this->m_distro = $distroMenu->run();

        if (iRAP\CoreLibs\StringLib::endsWith($this->m_distro->getLocation(), ".iso", false))
        {
            $isoName = str_replace(' ', '_', $this->m_distro->getName()) . '.iso';
            
            # Check the iso exists and grab it if not
            $isoLocation = ISOS_DIR . '/' . $isoName;

            if (!file_exists($isoLocation))
            {
                print "Grabbing ISO as you dont already have it." . PHP_EOL;
                $fetchCommand = 'wget -O ' . $isoLocation . ' ' . $this->m_distro->getLocation();
                shell_exec($fetchCommand);
            }

            $this->m_installationSource = ISOS_DIR . '/' . $isoName;
        }
        else
        {
            $this->m_installationSource = $this->m_distro->getLocation();
        }
        
        $this->m_ram = App::getInput("How much RAM (MB)?");
        $this->m_vCpuCount = App::getInput('Access to how many VCPUs?');
        $this->m_configuredDisk = new ConfiguredDisk($this->m_name);
    }
    
    
    /**
     * Deploy the VM!
     */
    public function deploy()
    {
        echo PHP_EOL . "Creating the disk(s)" . PHP_EOL;
        
        if ($this->m_configuredDisk->getFormat() === "qcow2")
        {
            $formatString = '-f qcow2 -o preallocation=metadata,lazy_refcounts=on ';
        }
        else
        {
            $formatString = '-f raw ';
        }
        
        // Create the directory for the disk to be placed into.
        \iRAP\CoreLibs\Filesystem::mkdir(dirname($this->m_configuredDisk->getFilepath()));
        
        $createDiskCommand = 
            'qemu-img create ' .
            $formatString .
            $this->m_configuredDisk->getFilepath() . ' ' .
            $this->m_configuredDisk->getSize() . 'G' . PHP_EOL;
        
        echo PHP_EOL . "Creating the disk with command: $createDiskCommand" . PHP_EOL;
        shell_exec($createDiskCommand);
        
        # Temporary hack. Set to 777 so Virt manager definitely has access to the disk.
        shell_exec("chmod 777 " . $this->m_configuredDisk->getFilepath());
        
        echo PHP_EOL . "Running the VM installer. This will take a while: " . $this->getInstallCommand() . PHP_EOL;
        shell_exec($this->getInstallCommand());
    }
    
    
    /**
     * Get the command for installing the distro in the CLI.
     * @return string
     */
    private function getInstallCommand()
    {
        $switches = array(
            '--connect qemu:///system ',
            #'--noautoconsole',
            #'--vnc', # you cannot have nographics on if this is and vice-versa
            '--nographics',
            '--os-type linux',
            '--accelerate',
            '--hvm', # kvm does not have paravirt, thats xen only.
            #'--network network=bridge:kvmbr0,model=virtio', # commenting this out results in using kvms default 192.168.122.1 virbr0 and VM's will not have public IPs
            '--network network=default,model=virtio'
        );
        
        $switches['NAME'] = '--name ' . $this->m_name;
        $switches['DISTRO'] = '--os-variant ' . $this->m_distro->getOsVariant();
        $switches['RAM'] = '--ram ' . $this->m_ram;
        $switches['VCPUS'] = '--vcpus ' . $this->m_vCpuCount;
        $switches['INSTALLATION_SRC'] = '--location '  . $this->m_installationSource;
        
        $switches['DISK'] = 
            '--disk ' . $this->m_configuredDisk->getFilepath() .
            ',bus=virtio' . 
            ',format=' . $this->m_configuredDisk->getFormat() . 
            ',cache=' . $this->m_configuredDisk->getCacheMode();     
        
        # Setting the console allows us to actually see output and answer prompts.
        $extraDistroSpecificArgs = $this->m_distro->getExtraArgs();
        
        if ($extraDistroSpecificArgs !== "")
        {
            $extraDistroSpecificArgs = " " . $extraDistroSpecificArgs;
        }
        
        $switches['EXTRA_ARGS'] = 
            '--extra-args "' .
                'console=ttyS0 ' .
                $this->m_distro->getKickstartArgKeyword() . '=' . $this->m_distro->getKickstartUrl() . 
                $extraDistroSpecificArgs .
            '"';
        
        $switchValues = array_values($switches);

        $join = ' \\' . PHP_EOL;
        return 'virt-install ' . implode($join, $switchValues);
    }
}
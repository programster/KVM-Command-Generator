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
        
        # Create the disk and guest db objects
        $disk = new Disk(array('path' => $this->m_configuredDisk->getFilepath()));
        $disk->save();
        
        $guest = new Guest(array('name' => $this->m_name));
        $guest->save();
        
        $guestDisk = new GuestDisk(array('guest_id' => $guest->get_id(), 'disk_id' => $disk->get_id()));
        $guestDisk->save();
        
        if (DEBUG)
        {
            print PHP_EOL;
            print "Run the following command to install the guest and watch it install." . PHP_EOL;
            print $this->getInstallCommand() . PHP_EOL;
        }
        else
        {
            echo PHP_EOL . "Running the VM installer. " . PHP_EOL . 
                "This will proceed in the background and take quite a while. " . 
                "You will know when the guest has finished installation when it is no longer running." . PHP_EOL;
            
            shell_exec($this->getInstallCommand());
        }
    }
    
    
    /**
     * Get the command for installing the distro in the CLI.
     * @return string
     */
    private function getInstallCommand()
    {
        $switches = array(
            '--connect qemu:///system ',
            '--nographics',
            '--os-type linux',
            '--accelerate',
            '--hvm', # kvm does not have paravirt, thats xen only.
        );
        
        if (USE_NETWORK_BRIDGE)
        {
            $switches['NETWORK'] = '--network bridge=' . BRIDGE_NAME . ',model=virtio';
        }
        else
        {
            # No bridge being used. Guests will use KVM's default 192.168.122.1 virbr0 and VM's 
            # will not have public IPs
            $switches['NETWORK'] = '--network network=default,model=virtio';
        }
        
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
        
        // when debugging, we want to automatically connect to the console to watch
        // the installation to check nothing is wrong with kickstart files etc.
        if (DEBUG === FALSE)
        {
            $switches['NO_AUTO_CONSOLE'] = '--noautoconsole ';
        }
        
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
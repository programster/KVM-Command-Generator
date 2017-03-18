<?php

/*
 * This is a script to help create vm guests.
 * References:
 * http://www.centos.org/docs/5/html/5.2/Virtualization/sect-Virtualization-Installing_guests-Create_a_guest_using_virt_install.html
 * http://arstechnica.com/civis/viewtopic.php?f=16&t=1165804
 */

require_once(__DIR__ . '/bootstrap.php');

// Run migrations
$db = ConnectionHandler::getConnection();
$migrationManager = new iRAP\Migrations\MigrationManager(__DIR__ . '/migrations', $db);
$migrationManager->migrate();

class App
{
    public static function main()
    {
        $actionMenu = new \Programster\CliMenu\ActionMenu("Main Menu");
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Install fresh VM", function(){ self::installVM(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Clone VM", function(){ self::cloneVM(); }));
        
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Create Snapshot", function(){ self::createSnapshot(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Delete Snapshot", function(){ self::deleteSnapshot(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Restore Snapshot", function(){ self::restoreSnapshot(); }));
        
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Rename VM", function(){ self::renameVM(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Shutdown VM", function(){ self::shutdownVm(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Start VM", function(){ self::startVM(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Quit", function(){ die(PHP_EOL . "Thank you come again." . PHP_EOL); }));
            
        while (true)
        {
            $actionMenu->run();
        }
    }
    
    
    /**
     * Callback to handle the users request to install a fresh VM from scratch
     */
    private static function installVM()
    {
        $virtualMachine = new VirtualMachine();
        $virtualMachine->deploy();
    }
    
    
    /**
     * Callback to handle the users request to clone an existing VM.
     * This is almost exactly like createSnapshot, just with a subtle difference.
     */
    private static function cloneVM()
    {
        echo "User chose to clone a VM." . PHP_EOL;
    }
    
    
    /**
     * Callback to handle the users request to clone an existing VM.
     */
    private static function createSnapshot()
    {
        $vm = self::getChosenVmFromUser();
        
        $externalSnapshotOption = new Programster\CliMenu\MenuOption(
            "External (fast)", 
            function() use($vm) { self::createExternalSnapshot($vm); }
        );
        
        $internalSnapshotOption = new Programster\CliMenu\MenuOption(
            "Internal (requires qcow2 based guest)", 
            function() use($vm) { self::createInternalSnapshot($vm); }
        );
        
        $snapshotMenu = new Programster\CliMenu\ActionMenu("Snapshot Type");
        $snapshotMenu->addOption($externalSnapshotOption);
        $snapshotMenu->addOption($internalSnapshotOption);
        $snapshotMenu->run();        
    }
    
    
    /**
     * Create an external snapshot on the specified VM
     * @param string $vmName - identifier for the VM.
     */
    private static function createExternalSnapshot($vmName)
    {
        $snapshotName = self::getInput("Snapshot name: ");
        
        $cmd = 
            'bash ' . __DIR__ . '/create-external-snapshot.sh' .
            ' "' . $vmName . '"' . 
            ' "' . $snapshotName . '"' . 
            ' "' . KVM_DIR . '"';
        
        shell_exec($cmd);
    }
    
    
    /**
     * Create an internal snapshot on the specified VM
     * @param string $vmName - identifier for the VM.
     */
    private static function createInternalSnapshot($vmName)
    {
        if (false)
        {
            $snapshotName = self::getInput("Snapshot Name: ");
            $description = self::getInput("Snapshot Description: ");
            
            // Strip out any quotiation marks that could cause our command to fail later.
            $snapshotName = iRAP\CoreLibs\StringLib::replace('"', '', $snapshotName);
            $description = iRAP\CoreLibs\StringLib::replace('"', '', $description);
            
            $cmd = 'virsh snapshot-create-as ' . $vmName . 
                    ' "' . $snapshotName . '"' . 
                    ' "' . $description . '"';
            
            shell_exec($cmd);
        }
        else
        {
            print "Cannot yet support both internal and external snapshots, so keeping internal only" . PHP_EOL;
        }
    }
    
    
    /**
     * Get the user to choose a VM.
     * @return string - the name of the VM.
     */
    private static function getChosenVmFromUser()
    {
        $vms = \iRAP\CoreLibs\Filesystem::getDirectories(KVM_DIR, false, false);
        $vmsMenu = new Programster\CliMenu\ValueMenu("Which VM?");
        
        foreach ($vms as $vm)
        {
            $vmsMenu->addOption($vm, $vm);
        }
        
        return $vmsMenu->run();
    }
    
    
    /**
     * Callback to handle the users request to delete one of a VMs snapshots
     */
    private static function deleteSnapshot()
    {
        echo "User chose to delete a VM's snapshot." . PHP_EOL;
    }
    
    
    /**
     * Callback to handle the users request to rename a VM.
     */
    private static function renameVM()
    {
        echo "User chose to rename a VM." . PHP_EOL;
    }
    
    
    /**
     * Load the distros from the distros.json file that anyone can easily edit.
     * @return Array<Distro>
     */
    public static function loadDistrosFromFile()
    {
        $distrosJson = file_get_contents(__DIR__ . '/distros.json');
        $distrosArray = json_decode($distrosJson, $arrayForm=true);
        $distros = array();

        foreach ($distrosArray as $distroyArrayForm)
        {
            $distros[] = Distro::loadFromArray($distroyArrayForm);
        }
        
        return $distros;
    }
    
    
    /**
     * Fetches input from the user by asking them a question.
     * @param string $question
     * @param array $possibleAnswers - optionally specify a choice of answers that user must choose from
     * @return String - the answer the user provides.
     */
    public static function getInput($question, $possibleAnswers=array())
    {
        $possAnswerString = implode('|', $possibleAnswers);

        $question .= ' (' . $possAnswerString . ') ';
        $answer = readline($question);

        if (count($possibleAnswers) > 0)
        {
            if (!in_array($answer, $possibleAnswers))
            {
                $answer = App::getInput($question, $possibleAnswers);
            }
        }

        return $answer;
    }
}

App::main();

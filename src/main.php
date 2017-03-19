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
        $guestToClone = self::getChosenGuestFromUser();
        
        $snapshots = SnapshotTable::getInstance()->loadWhereAnd(array(
            'guest_id' => $guestToClone->get_id()
        ));
        
        if (count($snapshots) == 0)
        {
            print "Guest has no snapshots to create a clone from. Please snapshot guest first." . PHP_EOL;
        }
        else
        {
            $snapshotMenu = new Programster\CliMenu\ValueMenu("Snapshots");
            
            foreach ($snapshots as $snapshot)
            {
                /* @var $snapshot Snapshot */
                $snapshotMenu->addOption($snapshot->get_name(), $snapshot);
            }
            
            $chosenSnapshot = $snapshotMenu->run();
            
            self::createGuestFromSnapshot($chosenSnapshot);
        }
    }
    
    
    /**
     * Create a guest by creating an overlay image that uses an existing backing/snapshot file
     * as its own backing file.
     * @param Snapshot $snapshot
     */
    private function createGuestFromSnapshot(Snapshot $snapshot)
    {
        $guestName = self::getInput("Guest name: ");
        
        /* @var $backingDisk Disk */
        $backingDisk = DiskTable::getInstance()->load($snapshot->get_disk_id());
        
        $randString = iRAP\CoreLibs\StringLib::generateRandomString(
            10, 
            iRAP\CoreLibs\StringLib::PASSWORD_DISABLE_SPECIAL_CHARS
        );
        
        $newGuestDiskPath = KVM_DIR . '/' . time() . '_' . $randString . '.qcow2';
                
        # Create new overlay image that will point back to the snapshot disk
        $createDiskCommand = 
            'qemu-img create -f qcow2' .
            ' -b ' . $backingDisk->get_path() .
            ' ' . $newGuestDiskPath;
        
        shell_exec($createDiskCommand);
        
        /* @var $baseGuest Guest */
        $baseGuest = GuestTable::getInstance()->load($snapshot->get_guest_id());
        
        $newDisk = new Disk(array('path' => $newGuestDiskPath));
        $newDisk->save();
        
        $dependency = new DiskDependency(array(
            'parent_disk_id' => $backingDisk->get_id(),
            'child_disk_id'  => $newDisk->get_id()
        ));
        $dependency->save();
        
        $newGuest = new Guest(array("name" => $guestName));
        $newGuest->save();
        
        $newGuestDisk = new GuestDisk(array(
            'guest_id' => $newGuest->get_id(),
            'disk_id'  => $newDisk->get_id()
        ));
        $newGuestDisk->save();
        
        // give the clone a snapshot off the bat which is the snapshot we are cloning from
        $newGuestSnapshot = new Snapshot(array(
            'name' => $snapshot->get_name(),
            'guest_id' => $newGuest->get_id(),
            'disk_id' => $snapshot->get_disk_id(),
            'mem_path' => $snapshot->get_mem_path()
        ));
        $newGuestSnapshot->save();
        
        
        # Generate a new xml file for the guest.
        # This takes care of changing the MAC address for us.
        $xmlPlaceholderFile = tempnam('/tmp', '');
        
        $cloneCommand = 
            'virt-clone' .
            ' --original ' . $baseGuest->get_name() .
            ' --name ' . $guestName .
            ' --file=' . $newGuestDiskPath .
            ' --preserve-data' .
            ' --print-xml > ' . $xmlPlaceholderFile;
        
        shell_exec($cloneCommand);
        shell_exec("virsh define $xmlPlaceholderFile");
        shell_exec("rm $xmlPlaceholderFile");
    }
    
    
    /**
     * Callback to handle the users request to clone an existing VM.
     */
    private static function createSnapshot()
    {
        $guest = self::getChosenGuestFromUser();
        
        if (false)
        {
            $externalSnapshotOption = new Programster\CliMenu\MenuOption(
                "External (fast)", 
                function() use($guest) { self::createExternalSnapshot($guest); }
            );
            
            
            $internalSnapshotOption = new Programster\CliMenu\MenuOption(
                "Internal (requires qcow2 based guest)", 
                function() use($guest) { self::createInternalSnapshot($guest); }
            );
            
            $snapshotMenu = new Programster\CliMenu\ActionMenu("Snapshot Type");
            $snapshotMenu->addOption($externalSnapshotOption);
            $snapshotMenu->addOption($internalSnapshotOption);
            $snapshotMenu->run();
        }
        else
        {
            self::createExternalSnapshot($guest);
        }
    }
    
    
    /**
     * Create an external snapshot on the specified VM
     * @param Guest $guest - the guest we will create a snapshot of.
     */
    private static function createExternalSnapshot(Guest $guest)
    {
        $snapshotName = self::getInput("Snapshot name: ");
        
        $stateCommand = 'virsh dominfo ' . $guest->get_name() . ' | grep "State" | cut -d " " -f 11';
        $state = shell_exec($stateCommand);
        
        $randString = iRAP\CoreLibs\StringLib::generateRandomString(10, iRAP\CoreLibs\StringLib::PASSWORD_DISABLE_SPECIAL_CHARS);
        $newDiskName = time() . '_' . $randString . '.qcow2';
        $newMemName = time() . '_' . $randString . '.mem';
        $newDiskFilepath = KVM_DIR . '/' . $newDiskName;
        
        if ($state === "running")
        {
            // create a live snapshot
            $newMemFilepath = KVM_DIR . '/' . $newMemName;
            
            $snapshotCommand = 
                'virsh snapshot-create-as' .
                ' --domain ' . $guest->get_name() . ' ' . $snapshotName .
                ' --diskspec vda,file=' . $newDiskFilepath . ',snapshot=external' .
                ' --memspec file=' . $newMemFilepath . ',snapshot=external' .
                ' --atomic';
        }
        else
        {
            // create a disk-only snapshot.
            $newMemFilepath = "";
            
            $snapshotCommand = 
                'virsh snapshot-create-as' .
                ' --domain ' . $guest->get_name() . ' ' . $snapshotName  .
                ' --diskspec vda,file=' . $newDiskFilepath . ',snapshot=external' .
                ' --disk-only' .
                ' --atomic';
        }
        
        $newOverlayDisk = new Disk(array('path' => $newDiskFilepath));
        $newOverlayDisk->save();
        
        $snapshot = new Snapshot(array(
            'name' => $snapshotName,
            'guest_id' => $guest->get_id(),
            'disk_id' => $newOverlayDisk->get_id(),
            'mem_path' => $newMemFilepath
        ));
        
        $snapshot->save();
        
        $originalDisks = GuestDiskTable::getInstance()->loadWhereAnd(array('guest_id' => $guest->get_id()));
        /* @var $originalGuestDisk GuestDisk */
        $originalGuestDisk = $originalDisks[0];
        
        $diskDependency = new DiskDependency(array(
            'parent_disk_id' => $originalGuestDisk->get_id(),
            'child_disk_id'  => $newOverlayDisk->get_id()
        ));
        
        $diskDependency->save();
        
        # delete the original disk being assigned to the guest, as it is now only a backing file
        # the new overlay image should be the only disk "assigned" to the guest.
        $originalGuestDisk->delete();
        
        $newGuestDisk = new GuestDisk(array(
            'guest_id' => $guest->get_id(), 
            'disk_id' => $newOverlayDisk->get_id())
        );
        
        $newGuestDisk->save();
        
        # Execute the snapshot!
        shell_exec($snapshotCommand);
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
    private static function getChosenGuestFromUser()
    {
        $guests = GuestTable::getInstance()->loadAll();
        $vmsMenu = new Programster\CliMenu\ValueMenu("Which VM?");
        
        foreach ($guests as $guest)
        {
            /* @var $guest Guest */
            $vmsMenu->addOption($guest->get_name(), $guest);
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

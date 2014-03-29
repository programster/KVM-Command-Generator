<?php

/*
 * This is a script to help create vm guests.
 * References:
 * http://www.centos.org/docs/5/html/5.2/Virtualization/sect-Virtualization-Installing_guests-Create_a_guest_using_virt_install.html
 * http://arstechnica.com/civis/viewtopic.php?f=16&t=1165804
 */

global $settings;

$settings = array(
    'SOURCE_DIR'        => dirname(__FILE__) . '/installation_media', # where iso to install from is
    'INSTALLATION_DIR'  => dirname(__FILE__) . '/vms', # where disk images will be stored
    'ISO_NAME'          => 'ubuntu-precise-mini.iso'
);



/**
 * Fetches input from the user by asking them a question.
 * @param string $question
 * @param array $possibleAnswers - optionally specify a choice of answers that user must choose from
 * @return String - the answer the user provides.
 */
function getInput($question, $possibleAnswers=array())
{
    $possAnswerString = implode('|', $possibleAnswers);
    
    $question .= ' (' . $possAnswerString . ') ';
    $answer = readline($question);

    if (count($possibleAnswers) > 0)
    {
        if (!in_array($answer, $possibleAnswers))
        {
            $answer = getInput($question, $possibleAnswers);
        }
    }

    return $answer;
}



/**
 * Check if we have already fetched the relevant installation media and fetch it if we haven't.
 * @param void
 * @return void
 */
function checkInstallationMedia()
{
    global $settings;

    print "Checking that you have the installation media and grabbing it if you dont." . PHP_EOL;

    $downloadIso = false;
    
    if (file_exists($settings['SOURCE_DIR']))
    {
        $isoPath = $settings['SOURCE_DIR'] . '/' . $settings['ISO_NAME'];
        
        if (!file_exists($isoPath))
        {
            $downloadIso = true;
        }
    }
    else
    {
        mkdir($settings['SOURCE_DIR']);
        $downloadIso = true;
    }

    if ($downloadIso)
    {
        $fetchCommand = 'wget -O ' . $settings['ISO_NAME'] . ' ' .
                        'http://archive.ubuntu.com/ubuntu/dists/precise-updates/main/installer-amd64/current/images/netboot/mini.iso';
        
        chdir($settings['SOURCE_DIR']);
        shell_exec($fetchCommand);
    }
}


/**
 * Configure all the various storage/disk aspects of the VM by asking the user questions.
 * @param Array $switches
 * @param String $vmName
 * @return Array
 */
function configureDisk($switches, $vmName)
{
    global $settings;
    
    $yesNoOptions = array('yes', 'no');

    # DISK PARAMS

    $autoManage = getInput("Auto place disk file?", $yesNoOptions);

    if ($autoManage)
    {
        $filepath = $settings['INSTALLATION_DIR'] . '/' . $vmName . '.img';
    }
    else
    {
        $filepath = getInput("Please specify the path to place the file:");
    }

    $options = array('sparse', 'dedicated');
    
    if (getInput("Use a sparse or dedicated storage file?", $options)=='sparse')
    {
        $isSparse = 'yes';
    }
    else
    {
        $isSparse = 'no';
    }

    $diskSize = getInput("How much allocated storage (in GB)?");
    
    $switches['DISK'] = '--disk ' . $filepath . ',size=' . $diskSize . ',sparse=' . $isSparse;

    return $switches;
}



function main()
{
    global $settings;
    
    checkInstallationMedia();

    # initialize the switches with the default settings
    $switches = array(
        '--connect qemu:///system ',
        #'--noautoconsole',
        #'--vnc', # you cannot have nographics on if this is and vice-versa
        '--nographics',
        '--os-type linux',
        '--os-variant=ubuntuprecise',
        '--accelerate',
        '--hvm', # kvm does not have paravirt, thats xen only.
        #'--network=bridge:br0', # commenting this out results in using kvms default 192.168.122.1 virbr0 and VM's will not have public IPs
        '--location='  . $settings['SOURCE_DIR'] . '/' . $settings['ISO_NAME'], # location works on isos as well, but have to use location if want extra args which is needed for instant cli install
        '--extra-args "console=ttyS0 ks=http://pastebin.com/raw.php?i=ddLQtuHz"' # Setting the console allows us to actually see output and answer prompts.
    );

    $vmName = getInput("Name for the VM?");
    $switches['NAME'] = '--name ' . $vmName;

    $switches = configureDisk($switches, $vmName);

    # END OF DISK

    $switches['RAM'] = '--ram=' . getInput("How much RAM (MB)?");
    $yesNoOptions = array('yes', 'no');
    

    # Unfortunately, if you don't specify this paramater, then you default to just one vcpu
    # instead of being able to access all of them
    $switches['VCPUS'] = '--vcpus=' . getInput('Access to how many VCPUs?');

    $switchValues = array_values($switches);

    $join = ' \\' . PHP_EOL;
    $command = 'sudo virt-install ' . implode($join, $switchValues);

    echo "Please run the following command:" . PHP_EOL .
         $command . PHP_EOL;
}


main();
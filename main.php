<?php

/*
 * This is a script to help create vm guests.
 * References:
 * http://www.centos.org/docs/5/html/5.2/Virtualization/sect-Virtualization-Installing_guests-Create_a_guest_using_virt_install.html
 * http://arstechnica.com/civis/viewtopic.php?f=16&t=1165804
 */

require_once(__DIR__ . '/distro.class.php');

global $settings;

# configure the distros that the user will be able to choose from.
$distros = array(
    # we use pastebin links rather than linking to github raw because for some reason github raw
    # will fail to download.
    new Distro(
       'Ubuntu 16.04', 
       'ubuntu16.04', # xenial not listed in options
       'http://us.archive.ubuntu.com/ubuntu/dists/xenial/main/installer-amd64/',
       'http://pastebin.com/raw/Q3Yq6EyQ', # no swap, no lvm, installs openssh-server
       'ks'
    ),
    new Distro(
       'Ubuntu 14.04', 
       'ubuntu14.04', 
       'http://us.archive.ubuntu.com/ubuntu/dists/trusty/main/installer-amd64/',
       'http://pastebin.com/raw.php?i=SrTvbt6Z',
       'ks'
    ),
    new Distro(
        'Debian 7', 
        'debian7',
        'http://ftp.us.debian.org/debian/dists/wheezy/main/installer-amd64/',
        'http://pastebin.com/raw.php?i=JEZn5Q5n',
        "url",
        "auto=true text hostname=debian domain=debian"
    ),
    new Distro(
       'Debian 8', 
       'debian8', 
       'http://ftp.us.debian.org/debian/dists/jessie/main/installer-amd64/',
       'http://pastebin.com/raw.php?i=3NmQCK42',
       "url",
       "auto=true text hostname=debian domain=debian"
    ),
    new Distro(
       'Debian 9', 
       'debian8', # stretch not yet listed in os variants yet
       'http://ftp.us.debian.org/debian/dists/stretch/main/installer-amd64/',
       'http://pastebin.com/raw/nFEkfBJA',
       "url",
       "auto=true text hostname=debian domain=debian"
    ),
    new Distro(
       'CentOS 6', 
       'centos6.5', 
       'ftp://ftp.mirrorservice.org/sites/mirror.centos.org/6/os/x86_64/',
       'http://pastebin.com/raw.php?i=4qi6WEYt',
       "ks"
    ),
    new Distro(
       'CentOS 7', 
       'centos7.0', 
       'ftp://ftp.mirrorservice.org/sites/mirror.centos.org/7/os/x86_64/',
       'http://pastebin.com/raw/KmV2cnET',
       "ks"
    ),
);

$settings = array(
    # where iso to install from is
    'SOURCE_DIR'            => __DIR__ . '/installation_media',
    
    # Where we are going to store the guest VMs disk images
    'INSTALLATION_DIR'      => __DIR__ . '/vms', 
    
    
    'DISTROS'               => $distros,
    
    # where will put generated script user must run as sudo
    'INSTALL_GUEST_SCRIPT'  => '/tmp/install-guest.sh' 
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
function init()
{
    global $settings;
    
    if (!file_exists($settings['SOURCE_DIR']))
    {
        mkdir($settings['SOURCE_DIR']);
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
    $filepath = $settings['INSTALLATION_DIR'] . '/' . $vmName . '.img';
    
    
    $diskSize = getInput("How much allocated storage (in GB)?");
    
    $answers = array("y", "n");
    $answer = getInput("Is the VM's disk file going to be stored on a remote server's NFS?", $answers);
    
    switch ($answer)
    {
        case 'y':
        {
            $switches['DISK'] = '--disk ' . $filepath . ',bus=virtio,format=qcow2,cache=none';
        }
        break;
        
        case 'n':
        {
            $switches['DISK'] = '--disk ' . $filepath . ',bus=virtio,format=qcow2';
        }
        break;
    }
    
    $createDiskCmd = 
        'qemu-img create ' .
        '-f qcow2 ' .
        '-o preallocation=metadata,lazy_refcounts=on ' .
        $filepath . ' ' .
        $diskSize . 'G' . PHP_EOL;
    
    file_put_contents($settings['INSTALL_GUEST_SCRIPT'], $createDiskCmd);
    shell_exec($createDiskCmd);
    
    return $switches;
}


/**
 * Ask the user for what distro they wish to deploy and grab it if we do not already have it.
 * This also sets up the switches that depend on the distro specified
 * @param Array $switches
 * @return Array - the modified switches
 */
function configureDistro($switches)
{
    global $settings;
    
    $distros = $settings['DISTROS'];
    
    # DISK PARAMS
    $filepath = $settings['INSTALLATION_DIR'] . '/' . $vmName . '.img';
    
    $distroIndex = -1;
    
    $numDistros = count($distros);
    while ($distroIndex < 0 || $distroIndex >= $numDistros)
    {
        print "Which distro?" . PHP_EOL;
        
        foreach($distros as $index => $distroOption)
        {
            print "[$index] " . $distroOption->getName() . PHP_EOL;
        }
        
        $distroIndex = intval(readline());
    }
    
    /* @var $chosenDistro Distro */
    $chosenDistro = $distros[$distroIndex];
    
    
    $switches['DISTRO'] = '--os-variant ' . $chosenDistro->getOsVariant();
    
    $revLocation = strtolower(strrev($chosenDistro->getLocation()));
    
    if (substr($revLocation, 0, 3) == "osi") # not all "locations" are isos
    {
        $isoName = str_replace(' ', '_', $chosenDistro->getName()) . '.iso';
        
        # Check the iso exists and grab it if not
        $isoLocation = $settings['SOURCE_DIR'] . '/' . $isoName;
        
        if (!file_exists($isoLocation))
        {
            print "Grabbing ISO as you dont already have it." . PHP_EOL;
            $fetchCommand = 'wget -O ' . $isoLocation . ' ' . $chosenDistro->getLocation();
            shell_exec($fetchCommand);
        }
        
        $switches['INSTALLATION_SRC'] = '--location '  . $settings['SOURCE_DIR'] . '/' . $isoName;
    }
    else
    {
        $switches['INSTALLATION_SRC'] = '--location '  . $chosenDistro->getLocation();
    }
    
    
    
    $kickstartFile = $chosenDistro->getKickstartUrl();
    $userKs = getInput('Specify the url to a kickstart file if you want to override the default: ');
    if (!empty($userKs))
    {
        $kickstartFile = $userKs;
    }
    
    # Setting the console allows us to actually see output and answer prompts.
    $extraDistroSpecificArgs = $chosenDistro->getExtraArgs();
    if ($extraDistroSpecificArgs !== "")
    {
        $extraDistroSpecificArgs = " " . $extraDistroSpecificArgs;
    }
    
    $switches['EXTRA_ARGS'] = 
        '--extra-args "' .
            'console=ttyS0 ' .
            $chosenDistro->getKickstartArgKeyword() . '=' . $kickstartFile . 
            $extraDistroSpecificArgs .
        '"';

    return $switches;
}



function main()
{
    global $settings;
    
    # Create the vms installation dir if it doesn't already exist.
    @mkdir($settings['INSTALLATION_DIR']);
    
    init();
    
    # initialize the switches with the default settings
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
    
    $vmName = getInput("Name for the VM?");
    $switches['NAME'] = '--name ' . $vmName;
    
    $switches = configureDistro($switches);
    
    $switches = configureDisk($switches, $vmName);
    
    # END OF DISK
    
    $switches['RAM'] = '--ram ' . getInput("How much RAM (MB)?");    
    
    # Unfortunately, if you don't specify this paramater, then you default to just one vcpu
    # instead of being able to access all of them
    $switches['VCPUS'] = '--vcpus ' . getInput('Access to how many VCPUs?');
    
    $switchValues = array_values($switches);
    
    $join = ' \\' . PHP_EOL;
    $command = 'virt-install ' . implode($join, $switchValues);
    
    
    # Append to the script because the command to create the disk is already in there.
    file_put_contents($settings['INSTALL_GUEST_SCRIPT'], $command, FILE_APPEND);
    
    # Tell the user what they now need to do in order to install the guest.
    echo "please run the following command to install the guest" . PHP_EOL;
    echo "sudo bash " . $settings['INSTALL_GUEST_SCRIPT'] . PHP_EOL;
}


main();

<?php

/*
 * This is a script to help create vm guests.
 * References:
 * http://www.centos.org/docs/5/html/5.2/Virtualization/sect-Virtualization-Installing_guests-Create_a_guest_using_virt_install.html
 * http://arstechnica.com/civis/viewtopic.php?f=16&t=1165804
 */

require_once(__DIR__ . '/bootstrap.php');


class App
{
    public static function main()
    {
        $actionMenu = new \Programster\CliMenu\ActionMenu("Main Menu");
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Install fresh VM", function(){ self::installVM(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Clone VM", function(){ self::cloneVM(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Create Snapshot", function(){ self::createSnapshot(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Delete Snapshot", function(){ self::deleteSnapshot(); }));
        $actionMenu->addOption(new Programster\CliMenu\MenuOption("Rename VM", function(){ self::renameVM(); }));
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
        echo "User chose to snapshot a VM." . PHP_EOL;
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

<?php

/* 
 * Put all initialization here.
 */


require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/Settings.php');

$classDirs = array(
    __DIR__
);

# Create the autoloader for autoloading our own classes when necessary.
new \iRAP\Autoloader\Autoloader($classDirs);

// Create the directories where necessary.
if (!file_exists(ISOS_DIR))
{
    \iRAP\CoreLibs\Filesystem::mkdir(ISOS_DIR);
}

if (!file_exists(VM_DIR))
{
    \iRAP\CoreLibs\Filesystem::mkdir(VM_DIR);
}

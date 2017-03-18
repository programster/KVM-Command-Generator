<?php

/* 
 * Feel free to change your default settings below.
 */

# Place to stick any distro ISO's for installing VMs from scratch with.
define('ISOS_DIR', __DIR__ . '/installation_media');

# Where we are going to store the guest VMs
#define('VM_DIR', __DIR__ . '/vms'); 
define('VM_DIR', '/kvm/vms');

# Define the path for the script we will write the command to install with.
define('INSTALL_GUEST_SCRIPT', '/tmp/install-guest.sh'); 

# Developers can flip this to true in development to help with diagnosing problems
define('DEBUG', false);

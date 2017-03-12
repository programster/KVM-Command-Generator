KVM-Manager
=====================

This CLI based application aims to simplify using KVM on a single **Ubuntu 16.04**  host.  For Ubuntu 14.04 support, please check out the previous releases. 

## Requirements
* Ubuntu 16.04 host (other distros will probably work, but not officially supported).
* PHP 7.0 CLI. You can install this with:

```
sudo apt-get install php7.0-cli -y
```

## Features
* Easy Guest Installation 
	* To set up a fresh guest from scratch, you just choose the option to deploy a guest, answer a bunch of multiple choice questions, and then let the tool do its job, automatically installing and configuring the guest for you. 

* Easy Customization
	* Add your own distros to install as guests by editing the `distros.json` file. 
	* Change where all the virtual machines are kept by editing the `Settings.php` file. 


## Default Accounts
Below are the default login details for guests installed using the default kickstart scripts.

* **Debian**
    * Username: root
    * Password: root
* **Ubuntu**
    * Username: ubuntu
    * Password: ubuntu
* **CentOS 6**
    * Username: root
    * Password: centos
* **CentOS 7 (recommend 1024+ MB RAM for installation)
    * Username: admin
    * Password: changeme123
    * Root Password: centos


## Troublieshooting
The debian 8.1 guest image has an issue related to systemd, in which you will not see the console. Following [this post](https://unix.stackexchange.com/questions/203768/debian-8-kvm-guest-loading-initial-ramdisk) to resolve the issue after installing the guest.

Ubuntu 16.04 guests have an issue when connecting to the console. [Refer here to fix](http://unix.stackexchange.com/questions/288344/accessing-console-of-ubuntu-16-04-kvm-guest).

## Features In Development
* Snapshot Management (internal and external).
* Basic Cloning (using virt-clone which copies the disk, gives a new name and mac)
* Rapid Thin Provision Cloning (using external snapshots for instant cloning and storage savings.)
* Guest renaming (automatically working around issues with renaming guests that have snapshots)
* Guest deletion (automatically working around issues with renaming guests that have snapshots)
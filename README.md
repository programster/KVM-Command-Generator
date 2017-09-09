KVM-Command-Generator
=====================

Simple CLI script to help witht he installation/deployment of KVM guests on Ubuntu 16.04. For Ubuntu 14.04 support, please check out the previous releases. This tool includes the automatic retrieval and usage of the necessary ISO images, kickstart scripts, and generation of storage file for the guest.


## Installation Instructions

### Debian 9
* [Turn your Debian 9 server into a KVM host](http://blog.programster.org/set-up-debian-9-kvm-server) if you haven't already.
* Install the PHP CLI with: `sudo apt-get install php7.0-cli`
* Go to the [releases page](https://github.com/programster/KVM-Command-Generator/releases) and download the latest one for Debian 9.
* Extract the tar.gz file. `tar --extract --gzip --file [filename]`
* Navigate within the extracted folder: `cd KVM-Command-Generator`
* Execute the tool: `php main.php`

### Ubuntu 16.04
* [Turn your Ubuntu 16.04 server into a KVM host](http://blog.programster.org/set-up-ubuntu-16-04-KVM-server) if you haven't already.
* Install the PHP CLI with: `sudo apt-get install php7.0-cli`
* Go to the [releases page](https://github.com/programster/KVM-Command-Generator/releases) and download the latest one for Ubuntu 16.04.
* Extract the tar.gz file. `tar --extract --gzip --file [filename]`
* Navigate within the extracted folder: `cd KVM-Command-Generator`
* Execute the tool: `php main.php`



## Default Accounts
The information below contains the default login details for the guests created using this tool with the default kickstart scripts. I would recommend creating one of each type of guest you want, before tweaking it with the login details/setup you want, and then just cloning that guest in future in order to create more guests. This is much faster and more convenient.

### Debian 8 & 9
* Username: root
* Password: root

### Ubuntu 14.04 & 16.04
* Username: ubuntu
* Password: ubuntu

### CentOS 6
* Root Password: centos

### CentOS 7 (recommend 1024+ MB RAM for installation)
* Username: admin
* Password: changeme123
* Root Password: centos


## Troubleshooting
The debian 8 and 9 guest images have an issue related to systemd, in which you will not see the console. Following [this post](https://unix.stackexchange.com/questions/203768/debian-8-kvm-guest-loading-initial-ramdisk) to resolve the issue after installing the guest.

Ubuntu 16.04 guests have an issue when connecting to the console. [Refer here to fix](http://unix.stackexchange.com/questions/288344/accessing-console-of-ubuntu-16-04-kvm-guest).

KVM-Command-Generator
=====================

Simple CLI script to help witht he installation/deployment of KVM guests on Ubuntu 14.04. This includes the automatic retrieval and usage of the necessary ISO images, kickstart scripts, and generation of storage file for the guest.


## Default Accounts
Below are the default login details for guests installed using the default kickstart scripts.
### Debian
* Username: root
* Password: root

### Ubuntu
* Username: ubuntu
* Password: ubuntu

### CentOS 6
* Root Password: centos

### CentOS 7 (recommend 1024+ MB RAM for installation)
* Username: admin
* Password: changeme123
* Root Password: centos


## Troublieshooting
The debian 8.1 guest image has an issue related to systemd, in which you will not see the console. Following [this post](https://unix.stackexchange.com/questions/203768/debian-8-kvm-guest-loading-initial-ramdisk) to resolve the issue after installing the guest.

Ubuntu 16.04 guests have an issue when connecting to the console. [Refer here to fix](http://unix.stackexchange.com/questions/288344/accessing-console-of-ubuntu-16-04-kvm-guest).

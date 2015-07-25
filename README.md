KVM-Command-Generator
=====================

Simple CLI script to generate the virt-install command necessary to install ubuntu 12 kvm guests. Also manages the automatic retrieval and usage of the ubuntu mini.iso


## Default Accounts
Below are the default login details for guests installed using the default kickstart scripts.
### Debian
Username: root
Password: root

### Ubuntu
Username: ubuntu
Password: ubuntu


## Extra Issues
The debian 8.1 guest image has an issue related to systemd, in which you will not see the console. Following [this post](https://unix.stackexchange.com/questions/203768/debian-8-kvm-guest-loading-initial-ramdisk) to resolve the issue after installing the guest.

#!/bin/bash
# This bash script is for creating an external snapshot of the specified guest, with the 
# specified name.

EXPECTED_NUM_ARGS=3;

if [ "$#" -ne $EXPECTED_NUM_ARGS ]; then
    echo "Create external snapshot script expects 3 arguments."
fi

# These should all be passed in and in the specified order.
DOMAIN=$1
SNAPSHOT_NAME=$2
VM_FOLDER=$3


SNAPSHOT_FOLDER="`echo $VM_FOLDER`/`echo $DOMAIN`/snapshots/`echo SNAPSHOT_NAME`"
mkdir -p $SNAPSHOT_FOLDER

MEM_FILE="`echo $SNAPSHOT_FOLDER`/mem.qcow2"
DISK_FILE="`echo $SNAPSHOT_FOLDER`/disk.qcow2"

# Find out if running or not
STATE=`virsh dominfo $DOMAIN | grep "State" | cut -d " " -f 11`

if [ "$STATE" = "running" ]; then
    virsh snapshot-create-as \
    --domain $DOMAIN $SNAPSHOT_NAME \
    --diskspec vda,file=$DISK_FILE,snapshot=external \
    --memspec file=$MEM_FILE,snapshot=external \
    --atomic
else
    virsh snapshot-create-as \
    --domain $DOMAIN $SNAPSHOT_NAME \
    --diskspec vda,file=$DISK_FILE,snapshot=external \
    --disk-only \
    --atomic
fi
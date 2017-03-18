#!/bin/bash
# This bash script is for creating an external snapshot of the specified guest, with the 
# specified name.
# based on work at: http://bit.ly/2n7fvJN

EXPECTED_NUM_ARGS=3;

if [ "$#" -ne $EXPECTED_NUM_ARGS ]; then
    echo "Create external snapshot script expects 3 arguments."
fi

# These should all be passed in and in the specified order.
DOMAIN=$1
SNAPSHOT_NAME=$2
VM_FOLDER=$3

echo "Preparing for snapshot..."
SNAPSHOT_FOLDER="`echo $VM_FOLDER`/`echo $DOMAIN`/snapshots/`echo $SNAPSHOT_NAME`"
mkdir -p $SNAPSHOT_FOLDER

# Specify where to store the memory state if the VM is running.
MEM_FILE="`echo $SNAPSHOT_FOLDER`/mem.qcow2"

# Specify where all new writes/changes will go, going forward for the VM.
NEW_WRITES_FILE="`echo $SNAPSHOT_FOLDER`/disk.qcow2"

# Find out if running or not
STATE=`virsh dominfo $DOMAIN | grep "State" | cut -d " " -f 11`

if [ "$STATE" = "running" ]; then
    virsh snapshot-create-as \
    --domain $DOMAIN intermediary_snapshot \
    --diskspec vda,file=$VMS_DIR/$DOMAIN/snapshots/$SNAPSHOT_NAME/disk.qcow2,snapshot=external \
    --memspec file=$VMS_DIR/$DOMAIN/snapshots/$SNAPSHOT_NAME/mem,snapshot=external \
    --atomic
else
    virsh snapshot-create-as \
    --domain $DOMAIN intermediary_snapshot \
    --diskspec vda,file=$VMS_DIR/$DOMAIN/snapshots/$SNAPSHOT_NAME/disk.qcow2,snapshot=external \
    --disk-only \    
    --atomic
fi

virsh blockpull \
--domain $DOMAIN \
--path $VMS_DIR/$DOMAIN/snapshots/$SNAPSHOT_NAME/disk.qcow2 \
--wait \
--verbose

virsh snapshot-delete \
--domain $DOMAIN \
intermediary_snapshot \
--metadata

if [ "$STATE" = "running" ]; then
    rm $VMS_DIR/$DOMAIN/disk.qcow2
    rm $VMS_DIR/$DOMAIN/snapshots/$SNAPSHOT_NAME/mem
else
    rm $VMS_DIR/$DOMAIN/disk.qcow2
fi

echo "Finished preparation."
echo "Taking snapshot...."

if [ "$STATE" = "running" ]; then
    virsh snapshot-create-as \
    --domain $DOMAIN $SNAPSHOT_NAME \
    --diskspec vda,file=$VMS_DIR/$DOMAIN/disk.qcow2,snapshot=external \
    --memspec file=$VMS_DIR/$DOMAIN/snapshots/$SNAPSHOT_NAME/mem,snapshot=external \
    --atomic
else
    virsh snapshot-create-as \
    --domain $DOMAIN $SNAPSHOT_NAME \
    --diskspec vda,file=$VMS_DIR/$DOMAIN/disk.qcow2,snapshot=external \
    --disk-only \    
    --atomic
fi

echo "Snapshot taken."
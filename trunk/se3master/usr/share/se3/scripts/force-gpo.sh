#!/bin/bash


## $Id$ ##


# script sudo pour forcer les gpo

machine=$1
ip=$2

find /home/netlogon -maxdepth 1  -name *.$machine.lck -delete
if [ -d /home/netlogon/machine/$machine ]; then
    rm -fr /home/netlogon/machine/$machine 
fi 

/usr/share/se3/shares/shares.avail/logonpy-gpo.sh adminse3 $machine $ip XP 2>&1
exit $?


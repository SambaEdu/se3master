#!/bin/bash

# $Id$ #

#####Supprime une entr�e dans clients.ini, lanc� par parcs/delete.php#####

echo "Suppression de $1"

perl -pi -e "s,^$1=.*\n,," /var/se3/Progs/install/installdll/clients.ini
exit 0
